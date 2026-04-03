#!/usr/bin/env bash
set -Eeuo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "${SCRIPT_DIR}/.." && pwd)"

DEPLOY_CONFIG_FILE="${DEPLOY_CONFIG_FILE:-${REPO_ROOT}/.deploy.env}"
COMPOSE_ENV_FILE="${REPO_ROOT}/.env"

DEFAULT_BACKUP_ROOT="${REPO_ROOT}/backups"
DEFAULT_STATE_DIR="${REPO_ROOT}/.deploy-state"
DEFAULT_COMPOSE_FILE="${REPO_ROOT}/docker-compose.yml"

CURRENT_RELEASE=""
CURRENT_RELEASE_DIR=""
MAINTENANCE_ENABLED=0
CUTOVER_COMPLETE=0

log() {
  printf '[deploy] %s\n' "$*" >&2
}

die() {
  log "ERROR: $*"
  exit 1
}

usage() {
  cat <<'EOF'
Usage:
  ./scripts/deploy.sh bootstrap-nginx
  ./scripts/deploy.sh backup-live
  ./scripts/deploy.sh deploy
  ./scripts/deploy.sh rollback
  ./scripts/deploy.sh status

Commands:
  bootstrap-nginx  Render managed host nginx configs and activate legacy mode.
  backup-live      Back up the current live DB, wp-content, and nginx config.
  deploy           Backup, sync live uploads, rebuild the stack, smoke test,
                   freeze old traffic if configured, take a final backup, switch
                   host nginx to the container config, and smoke test again.
  rollback         Switch host nginx back to the legacy config and unfreeze the
                   old site if a maintenance hook is configured.
  status           Show current deploy state and the active host nginx target.

Required setup:
  1. Copy .deploy.env.example to .deploy.env.
  2. Review every path/credential in .deploy.env.
  3. Run bootstrap-nginx once on the host before the first deploy.
EOF
}

require_cmd() {
  command -v "$1" >/dev/null 2>&1 || die "Missing command: $1"
}

run_shell_cmd() {
  local description="$1"
  local command="$2"
  log "${description}"
  (
    cd "$REPO_ROOT"
    bash -lc "$command"
  )
}

require_file_if_set() {
  local label="$1"
  local path="$2"
  [[ -z "$path" ]] && return 0
  [[ -f "$path" ]] || die "${label} does not exist: ${path}"
}

append_indented_file() {
  local file_path="$1"
  local indent="$2"

  [[ -z "$file_path" ]] && return 0
  require_file_if_set "Extra config file" "$file_path"

  while IFS= read -r line || [[ -n "$line" ]]; do
    printf '%s%s\n' "$indent" "$line"
  done < "$file_path"
}

resolve_repo_path() {
  local input_path="$1"

  [[ -n "$input_path" ]] || return 0
  if [[ "$input_path" = /* ]]; then
    printf '%s\n' "$input_path"
  else
    printf '%s/%s\n' "$REPO_ROOT" "${input_path#./}"
  fi
}

load_env() {
  local live_wp_content_dir=""

  [[ -f "$COMPOSE_ENV_FILE" ]] || die "Missing ${COMPOSE_ENV_FILE}"
  [[ -f "$DEPLOY_CONFIG_FILE" ]] || die "Missing ${DEPLOY_CONFIG_FILE}. Copy .deploy.env.example to .deploy.env"

  set -a
  # shellcheck disable=SC1090
  source "$COMPOSE_ENV_FILE"
  # shellcheck disable=SC1090
  source "$DEPLOY_CONFIG_FILE"
  set +a

  BACKUP_ROOT="$(resolve_repo_path "${BACKUP_ROOT:-$DEFAULT_BACKUP_ROOT}")"
  DEPLOY_STATE_DIR="$(resolve_repo_path "${DEPLOY_STATE_DIR:-$DEFAULT_STATE_DIR}")"
  COMPOSE_FILE_PATH="$(resolve_repo_path "${COMPOSE_FILE_PATH:-$DEFAULT_COMPOSE_FILE}")"
  COMPOSE_PROJECT_DIR="$(resolve_repo_path "${COMPOSE_PROJECT_DIR:-$REPO_ROOT}")"
  DATA_UPLOADS_DIR="${REPO_ROOT}/data/uploads"
  live_wp_content_dir="${LIVE_WP_CONTENT_DIR:-}"
  LIVE_UPLOADS_DIR="${LIVE_UPLOADS_DIR:-}"
  if [[ -z "$LIVE_UPLOADS_DIR" && -n "$live_wp_content_dir" ]]; then
    LIVE_UPLOADS_DIR="${live_wp_content_dir%/}/uploads"
  fi

  CONTAINER_PROXY_PORT="${CONTAINER_PROXY_PORT:-18020}"
  HEALTHCHECK_PATH="${HEALTHCHECK_PATH:-/}"
  HEALTHCHECK_TIMEOUT_SECONDS="${HEALTHCHECK_TIMEOUT_SECONDS:-60}"
  AUTO_ROLLBACK_ON_FAILURE="${AUTO_ROLLBACK_ON_FAILURE:-1}"
  BACKUP_DB_ENABLED="${BACKUP_DB_ENABLED:-1}"
  RSYNC_DELETE="${RSYNC_DELETE:-1}"
  HOST_NGINX_CLIENT_MAX_BODY_SIZE="${HOST_NGINX_CLIENT_MAX_BODY_SIZE:-64M}"
  HOST_NGINX_SITE_NAME="${HOST_NGINX_SITE_NAME:-askmortgageauthority}"
  HOST_NGINX_AVAILABLE_DIR="${HOST_NGINX_AVAILABLE_DIR:-/etc/nginx/sites-available}"
  HOST_NGINX_ENABLED_DIR="${HOST_NGINX_ENABLED_DIR:-/etc/nginx/sites-enabled}"
  HOST_NGINX_TEST_CMD="${HOST_NGINX_TEST_CMD:-sudo nginx -t}"
  HOST_NGINX_RELOAD_CMD="${HOST_NGINX_RELOAD_CMD:-sudo systemctl reload nginx}"
  LEGACY_TRY_FILES="${LEGACY_TRY_FILES:-/index.php?\$args}"

  LIVE_DB_HOST="${LIVE_DB_HOST:-${WORDPRESS_DB_HOST:-}}"
  LIVE_DB_PORT="${LIVE_DB_PORT:-3306}"
  LIVE_DB_NAME="${LIVE_DB_NAME:-${WORDPRESS_DB_NAME:-}}"
  LIVE_DB_USER="${LIVE_DB_USER:-${WORDPRESS_DB_USER:-}}"
  LIVE_DB_PASSWORD="${LIVE_DB_PASSWORD:-${WORDPRESS_DB_PASSWORD:-}}"

  SITE_DOMAINS="${SITE_DOMAINS:-}"
  SITE_PRIMARY_DOMAIN="${SITE_PRIMARY_DOMAIN:-}"
  if [[ -z "$SITE_PRIMARY_DOMAIN" && -n "$SITE_DOMAINS" ]]; then
    SITE_PRIMARY_DOMAIN="${SITE_DOMAINS%% *}"
  fi

  ACTIVE_SITE_CONF="${HOST_NGINX_ENABLED_DIR}/${HOST_NGINX_SITE_NAME}.conf"
  LEGACY_SITE_CONF="${HOST_NGINX_AVAILABLE_DIR}/${HOST_NGINX_SITE_NAME}.legacy.conf"
  CONTAINER_SITE_CONF="${HOST_NGINX_AVAILABLE_DIR}/${HOST_NGINX_SITE_NAME}.container.conf"

  mkdir -p "$BACKUP_ROOT" "$DEPLOY_STATE_DIR" "$DATA_UPLOADS_DIR"
}

require_common_env() {
  [[ -n "$SITE_DOMAINS" ]] || die "SITE_DOMAINS is required"
  [[ -n "$SITE_PRIMARY_DOMAIN" ]] || die "SITE_PRIMARY_DOMAIN is required"
  [[ -n "${LIVE_WP_CONTENT_DIR:-}" ]] || die "LIVE_WP_CONTENT_DIR is required"
  [[ -d "$LIVE_WP_CONTENT_DIR" ]] || die "LIVE_WP_CONTENT_DIR does not exist: $LIVE_WP_CONTENT_DIR"
  [[ -n "${LIVE_UPLOADS_DIR:-}" ]] || die "LIVE_UPLOADS_DIR is required"
  [[ -d "$LIVE_UPLOADS_DIR" ]] || die "LIVE_UPLOADS_DIR does not exist: $LIVE_UPLOADS_DIR"
  if [[ "$BACKUP_DB_ENABLED" == "1" ]]; then
    [[ -n "$LIVE_DB_NAME" ]] || die "LIVE_DB_NAME is required when BACKUP_DB_ENABLED=1"
    [[ -n "$LIVE_DB_USER" ]] || die "LIVE_DB_USER is required when BACKUP_DB_ENABLED=1"
    [[ -n "$LIVE_DB_PASSWORD" ]] || die "LIVE_DB_PASSWORD is required when BACKUP_DB_ENABLED=1"
  fi
  [[ -f "$COMPOSE_FILE_PATH" ]] || die "Compose file not found: $COMPOSE_FILE_PATH"
}

require_bootstrap_env() {
  [[ -n "${HOST_NGINX_SSL_CERT:-}" ]] || die "HOST_NGINX_SSL_CERT is required"
  [[ -n "${HOST_NGINX_SSL_KEY:-}" ]] || die "HOST_NGINX_SSL_KEY is required"
  [[ -n "${LEGACY_DOCROOT:-}" ]] || die "LEGACY_DOCROOT is required"
  [[ -n "${LEGACY_PHP_FPM_TARGET:-}" ]] || die "LEGACY_PHP_FPM_TARGET is required"
  [[ -d "${HOST_NGINX_AVAILABLE_DIR}" ]] || die "HOST_NGINX_AVAILABLE_DIR does not exist: ${HOST_NGINX_AVAILABLE_DIR}"
  [[ -d "${HOST_NGINX_ENABLED_DIR}" ]] || die "HOST_NGINX_ENABLED_DIR does not exist: ${HOST_NGINX_ENABLED_DIR}"
}

require_backup_tools() {
  require_cmd tar
  require_cmd gzip
  require_cmd rsync
  require_cmd docker
  require_cmd curl
  if [[ "$BACKUP_DB_ENABLED" == "1" ]] && ! command -v mysqldump >/dev/null 2>&1 && ! command -v mariadb-dump >/dev/null 2>&1; then
    die "Missing mysqldump or mariadb-dump"
  fi
}

db_dump_bin() {
  if command -v mysqldump >/dev/null 2>&1; then
    command -v mysqldump
  else
    command -v mariadb-dump
  fi
}

compose() {
  (
    cd "$COMPOSE_PROJECT_DIR"
    docker compose -f "$COMPOSE_FILE_PATH" "$@"
  )
}

release_id() {
  date -u +"%Y%m%dT%H%M%SZ"
}

snapshot_path() {
  local source_path="$1"
  local dest_path="$2"

  [[ -e "$source_path" ]] || return 0

  if [[ -L "$source_path" ]]; then
    local target_path
    target_path="$(readlink "$source_path")"
    printf '%s\n' "$target_path" > "${dest_path}.symlink-target"
    cp -L "$source_path" "$dest_path"
  else
    cp "$source_path" "$dest_path"
  fi
}

backup_database() {
  local stage_dir="$1"
  local dump_cmd
  local -a dump_args

  dump_cmd="$(db_dump_bin)"
  dump_args=(--single-transaction --routines --triggers --events -u "$LIVE_DB_USER")

  if [[ -n "${LIVE_DB_SOCKET:-}" ]]; then
    dump_args+=(--socket="$LIVE_DB_SOCKET")
  else
    [[ -n "$LIVE_DB_HOST" ]] || die "LIVE_DB_HOST is required when LIVE_DB_SOCKET is not set"
    dump_args+=(-h "$LIVE_DB_HOST" -P "$LIVE_DB_PORT")
  fi

  log "Backing up live database to ${stage_dir}/live-db.sql.gz"
  MYSQL_PWD="$LIVE_DB_PASSWORD" "$dump_cmd" "${dump_args[@]}" "$LIVE_DB_NAME" | gzip -c > "${stage_dir}/live-db.sql.gz"
}

backup_wp_content() {
  local stage_dir="$1"
  log "Backing up live wp-content to ${stage_dir}/live-wp-content.tar.gz"
  tar -C "$LIVE_WP_CONTENT_DIR" -czf "${stage_dir}/live-wp-content.tar.gz" .
}

backup_nginx_state() {
  local stage_dir="$1"
  local nginx_dir="${stage_dir}/nginx"

  mkdir -p "$nginx_dir"
  snapshot_path "$ACTIVE_SITE_CONF" "${nginx_dir}/active-site.conf"
  snapshot_path "$LEGACY_SITE_CONF" "${nginx_dir}/managed-legacy.conf"
  snapshot_path "$CONTAINER_SITE_CONF" "${nginx_dir}/managed-container.conf"
}

write_stage_manifest() {
  local stage_dir="$1"
  local stage_name="$2"

  cat > "${stage_dir}/manifest.env" <<EOF
release=${CURRENT_RELEASE}
stage=${stage_name}
created_at=$(date -u +"%Y-%m-%dT%H:%M:%SZ")
site_primary_domain=${SITE_PRIMARY_DOMAIN}
site_domains=${SITE_DOMAINS}
live_wp_content_dir=${LIVE_WP_CONTENT_DIR}
live_uploads_dir=${LIVE_UPLOADS_DIR}
compose_file=${COMPOSE_FILE_PATH}
container_proxy_port=${CONTAINER_PROXY_PORT}
backup_db_enabled=${BACKUP_DB_ENABLED}
EOF
}

run_backup_stage() {
  local stage_name="$1"
  local stage_dir="${CURRENT_RELEASE_DIR}/${stage_name}"

  mkdir -p "$stage_dir"
  if [[ "$BACKUP_DB_ENABLED" == "1" ]]; then
    backup_database "$stage_dir"
  else
    log "Skipping DB backup because BACKUP_DB_ENABLED=0"
  fi
  backup_wp_content "$stage_dir"
  backup_nginx_state "$stage_dir"
  write_stage_manifest "$stage_dir" "$stage_name"
}

sync_live_uploads() {
  local -a rsync_args

  rsync_args=(-a)
  if [[ "$RSYNC_DELETE" == "1" ]]; then
    rsync_args+=(--delete)
  fi
  if [[ -n "${LIVE_RSYNC_EXCLUDES_FILE:-}" ]]; then
    [[ -f "$LIVE_RSYNC_EXCLUDES_FILE" ]] || die "LIVE_RSYNC_EXCLUDES_FILE does not exist: $LIVE_RSYNC_EXCLUDES_FILE"
    rsync_args+=(--exclude-from="$LIVE_RSYNC_EXCLUDES_FILE")
  fi

  log "Syncing live uploads into ${DATA_UPLOADS_DIR}"
  rsync "${rsync_args[@]}" "${LIVE_UPLOADS_DIR}/" "${DATA_UPLOADS_DIR}/"
}

wait_for_container_http() {
  local deadline
  local url="http://127.0.0.1:${CONTAINER_PROXY_PORT}${HEALTHCHECK_PATH}"

  deadline=$((SECONDS + HEALTHCHECK_TIMEOUT_SECONDS))
  while (( SECONDS < deadline )); do
    if curl -fsS -H "Host: ${SITE_PRIMARY_DOMAIN}" "$url" >/tmp/ama-smoke-test.$$ 2>/dev/null; then
      if [[ -n "${SMOKE_TEST_EXPECTED_TEXT:-}" ]]; then
        if grep -Fq "$SMOKE_TEST_EXPECTED_TEXT" "/tmp/ama-smoke-test.$$"; then
          rm -f "/tmp/ama-smoke-test.$$"
          return 0
        fi
      else
        rm -f "/tmp/ama-smoke-test.$$"
        return 0
      fi
    fi
    sleep 2
  done

  rm -f "/tmp/ama-smoke-test.$$"
  die "Container smoke test failed for ${url}"
}

run_smoke_checks() {
  log "Running container smoke checks"
  wait_for_container_http
  compose exec -T wordpress wp core is-installed --allow-root >/dev/null
}

enable_maintenance_if_configured() {
  [[ -n "${LEGACY_ENABLE_MAINTENANCE_CMD:-}" ]] || return 0
  run_shell_cmd "Enabling legacy maintenance mode" "$LEGACY_ENABLE_MAINTENANCE_CMD"
  MAINTENANCE_ENABLED=1
}

disable_maintenance_if_configured() {
  [[ -n "${LEGACY_DISABLE_MAINTENANCE_CMD:-}" ]] || return 0
  run_shell_cmd "Disabling legacy maintenance mode" "$LEGACY_DISABLE_MAINTENANCE_CMD"
  MAINTENANCE_ENABLED=0
}

run_post_cutover_hook() {
  [[ -n "${POST_CUTOVER_CMD:-}" ]] || return 0
  run_shell_cmd "Running post-cutover hook" "$POST_CUTOVER_CMD"
}

run_post_rollback_hook() {
  [[ -n "${POST_ROLLBACK_CMD:-}" ]] || return 0
  run_shell_cmd "Running post-rollback hook" "$POST_ROLLBACK_CMD"
}

render_legacy_config() {
  cat <<EOF
server {
  listen 80;
  server_name ${SITE_DOMAINS};
  return 301 https://${SITE_PRIMARY_DOMAIN}\$request_uri;
}

server {
  listen 443 ssl;
  server_name ${SITE_DOMAINS};
  ssl_certificate ${HOST_NGINX_SSL_CERT};
  ssl_certificate_key ${HOST_NGINX_SSL_KEY};
  client_max_body_size ${HOST_NGINX_CLIENT_MAX_BODY_SIZE};
EOF
  append_indented_file "${HOST_NGINX_COMMON_EXTRA_FILE:-}" "  "
  cat <<EOF
  root ${LEGACY_DOCROOT};
  index index.php index.html;

  location / {
    try_files \$uri \$uri/ ${LEGACY_TRY_FILES};
  }

  location ~ \.php$ {
    include fastcgi_params;
    fastcgi_param SCRIPT_FILENAME \$document_root\$fastcgi_script_name;
    fastcgi_param QUERY_STRING \$query_string;
    fastcgi_pass ${LEGACY_PHP_FPM_TARGET};
  }

  location ~* \.(png|jpe?g|gif|svg|webp|css|js|ico)$ {
    expires max;
    log_not_found off;
  }
EOF
  append_indented_file "${HOST_NGINX_LEGACY_EXTRA_FILE:-}" "  "
  cat <<'EOF'
}
EOF
}

render_container_config() {
  cat <<EOF
server {
  listen 80;
  server_name ${SITE_DOMAINS};
  return 301 https://${SITE_PRIMARY_DOMAIN}\$request_uri;
}

server {
  listen 443 ssl;
  server_name ${SITE_DOMAINS};
  ssl_certificate ${HOST_NGINX_SSL_CERT};
  ssl_certificate_key ${HOST_NGINX_SSL_KEY};
  client_max_body_size ${HOST_NGINX_CLIENT_MAX_BODY_SIZE};
EOF
  append_indented_file "${HOST_NGINX_COMMON_EXTRA_FILE:-}" "  "
  cat <<EOF
  location / {
    proxy_pass http://127.0.0.1:${CONTAINER_PROXY_PORT};
    proxy_set_header Host \$host;
    proxy_set_header X-Real-IP \$remote_addr;
    proxy_set_header X-Forwarded-For \$proxy_add_x_forwarded_for;
    proxy_set_header X-Forwarded-Proto \$scheme;
  }
EOF
  append_indented_file "${HOST_NGINX_CONTAINER_EXTRA_FILE:-}" "  "
  cat <<'EOF'
}
EOF
}

test_and_reload_nginx() {
  run_shell_cmd "Testing host nginx config" "$HOST_NGINX_TEST_CMD"
  run_shell_cmd "Reloading host nginx" "$HOST_NGINX_RELOAD_CMD"
}

switch_active_site() {
  local target="$1"
  local previous_target=""
  local new_target=""

  case "$target" in
    legacy) new_target="$LEGACY_SITE_CONF" ;;
    container) new_target="$CONTAINER_SITE_CONF" ;;
    *) die "Unsupported nginx target: ${target}" ;;
  esac

  [[ -f "$new_target" ]] || die "Managed nginx config not found: $new_target"
  if [[ -L "$ACTIVE_SITE_CONF" ]]; then
    previous_target="$(readlink "$ACTIVE_SITE_CONF")"
  fi

  log "Switching active host nginx config to ${new_target}"
  ln -sfn "$new_target" "$ACTIVE_SITE_CONF"

  if ! bash -lc "$HOST_NGINX_TEST_CMD"; then
    if [[ -n "$previous_target" ]]; then
      ln -sfn "$previous_target" "$ACTIVE_SITE_CONF"
    fi
    die "Host nginx test failed after switching to ${target}"
  fi

  run_shell_cmd "Reloading host nginx" "$HOST_NGINX_RELOAD_CMD"
}

write_release_state() {
  local active_target="$1"

  cat > "${DEPLOY_STATE_DIR}/last-release.env" <<EOF
CURRENT_RELEASE=${CURRENT_RELEASE}
CURRENT_RELEASE_DIR=${CURRENT_RELEASE_DIR}
ACTIVE_TARGET=${active_target}
UPDATED_AT=$(date -u +"%Y-%m-%dT%H:%M:%SZ")
EOF
}

on_error() {
  local exit_code="$1"
  local line_no="$2"

  log "Failure on line ${line_no} (exit ${exit_code})"

  if (( CUTOVER_COMPLETE )) && [[ "$AUTO_ROLLBACK_ON_FAILURE" == "1" ]]; then
    log "Automatic rollback is enabled; switching host nginx back to legacy"
    switch_active_site legacy || true
    run_post_rollback_hook || true
    CUTOVER_COMPLETE=0
  fi

  if (( MAINTENANCE_ENABLED )); then
    disable_maintenance_if_configured || true
  fi

  exit "$exit_code"
}

bootstrap_nginx() {
  local bootstrap_dir

  load_env
  require_common_env
  require_bootstrap_env

  CURRENT_RELEASE="bootstrap-$(release_id)"
  CURRENT_RELEASE_DIR="${BACKUP_ROOT}/${CURRENT_RELEASE}"
  mkdir -p "$CURRENT_RELEASE_DIR"

  bootstrap_dir="${CURRENT_RELEASE_DIR}/bootstrap-nginx"
  mkdir -p "$bootstrap_dir"

  backup_nginx_state "$bootstrap_dir"

  log "Rendering managed legacy nginx config to ${LEGACY_SITE_CONF}"
  render_legacy_config > "$LEGACY_SITE_CONF"
  log "Rendering managed container nginx config to ${CONTAINER_SITE_CONF}"
  render_container_config > "$CONTAINER_SITE_CONF"

  switch_active_site legacy
  write_release_state legacy

  log "Managed nginx bootstrap completed"
}

backup_live() {
  load_env
  require_common_env
  require_backup_tools

  CURRENT_RELEASE="backup-$(release_id)"
  CURRENT_RELEASE_DIR="${BACKUP_ROOT}/${CURRENT_RELEASE}"
  mkdir -p "$CURRENT_RELEASE_DIR"

  run_backup_stage "live"
  write_release_state "$(status_active_target)"
  log "Live backup completed at ${CURRENT_RELEASE_DIR}"
}

status_active_target() {
  if [[ -L "$ACTIVE_SITE_CONF" ]]; then
    local current_target
    current_target="$(readlink "$ACTIVE_SITE_CONF")"
    case "$current_target" in
      "$LEGACY_SITE_CONF") printf 'legacy\n' ;;
      "$CONTAINER_SITE_CONF") printf 'container\n' ;;
      *) printf 'custom:%s\n' "$current_target" ;;
    esac
  elif [[ -e "$ACTIVE_SITE_CONF" ]]; then
    printf 'custom:%s\n' "$ACTIVE_SITE_CONF"
  else
    printf 'missing\n'
  fi
}

deploy() {
  load_env
  require_common_env
  require_bootstrap_env
  require_backup_tools

  trap 'on_error $? $LINENO' ERR

  CURRENT_RELEASE="release-$(release_id)"
  CURRENT_RELEASE_DIR="${BACKUP_ROOT}/${CURRENT_RELEASE}"
  mkdir -p "$CURRENT_RELEASE_DIR"

  run_backup_stage "pre-build"
  sync_live_uploads

  log "Building and starting Docker stack"
  compose up -d --build

  run_smoke_checks

  enable_maintenance_if_configured
  run_backup_stage "pre-cutover"
  sync_live_uploads
  run_smoke_checks

  switch_active_site container
  CUTOVER_COMPLETE=1

  run_smoke_checks
  run_post_cutover_hook
  disable_maintenance_if_configured

  write_release_state container
  log "Deploy completed successfully"
}

rollback() {
  load_env
  require_common_env
  require_bootstrap_env

  CURRENT_RELEASE="rollback-$(release_id)"
  CURRENT_RELEASE_DIR="${BACKUP_ROOT}/${CURRENT_RELEASE}"
  mkdir -p "$CURRENT_RELEASE_DIR"

  backup_nginx_state "$CURRENT_RELEASE_DIR"
  switch_active_site legacy
  run_post_rollback_hook
  disable_maintenance_if_configured || true
  write_release_state legacy

  log "Rollback completed"
}

status_cmd() {
  load_env

  printf 'Deploy config: %s\n' "$DEPLOY_CONFIG_FILE"
  printf 'Compose env: %s\n' "$COMPOSE_ENV_FILE"
  printf 'Compose file: %s\n' "$COMPOSE_FILE_PATH"
  printf 'Backup root: %s\n' "$BACKUP_ROOT"
  printf 'State dir: %s\n' "$DEPLOY_STATE_DIR"
  printf 'Active nginx target: %s\n' "$(status_active_target)"

  if [[ -f "${DEPLOY_STATE_DIR}/last-release.env" ]]; then
    printf '\nLast release state:\n'
    cat "${DEPLOY_STATE_DIR}/last-release.env"
  fi
}

main() {
  local action="${1:-help}"

  case "$action" in
    bootstrap-nginx) bootstrap_nginx ;;
    backup-live) backup_live ;;
    deploy) deploy ;;
    rollback) rollback ;;
    status) status_cmd ;;
    help|-h|--help) usage ;;
    *) usage; exit 1 ;;
  esac
}

main "$@"
