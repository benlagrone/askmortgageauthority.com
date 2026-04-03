#!/usr/bin/env bash
set -euo pipefail

# Install/activate WordPress.org plugins listed in plugins.txt using WP-CLI
# inside the running WordPress container.
# Usage: ./scripts/install-plugins.sh [plugins-file]
# Defaults to ./plugins.txt (one slug or URL per non-empty line; lines
# starting with # are comments).

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "${SCRIPT_DIR}/.." && pwd)"

PLUGINS_FILE="${1:-${REPO_ROOT}/plugins.txt}"
COMPOSE_FILE_PATH="${COMPOSE_FILE_PATH:-${REPO_ROOT}/docker-compose.yml}"
WORDPRESS_SERVICE="${WORDPRESS_SERVICE:-wordpress}"

if [[ ! -f "$PLUGINS_FILE" ]]; then
  echo "Plugins file not found: $PLUGINS_FILE" >&2
  exit 1
fi

if [[ "$COMPOSE_FILE_PATH" != /* ]]; then
  COMPOSE_FILE_PATH="${REPO_ROOT}/${COMPOSE_FILE_PATH#./}"
fi

compose_cmd=(docker compose -f "$COMPOSE_FILE_PATH")
if [[ -n "${COMPOSE_PROJECT_NAME:-}" ]]; then
  compose_cmd+=(-p "$COMPOSE_PROJECT_NAME")
fi

while IFS= read -r line; do
  # Skip comments/blank lines
  [[ -z "$line" || "$line" =~ ^# ]] && continue
  PLUGIN="$line"
  echo "Installing/activating plugin: $PLUGIN"
  "${compose_cmd[@]}" exec -T "$WORDPRESS_SERVICE" wp plugin install "$PLUGIN" --activate --force --allow-root
done < "$PLUGINS_FILE"
