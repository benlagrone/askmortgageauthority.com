# AMA WordPress container stack

Containerized WordPress/PHP-FPM with required extensions (gd, imagick, mysqli) plus an nginx frontend. Host nginx keeps TLS; it proxies HTTP to the nginx container on port 8081.

## Contents
- `Dockerfile` – builds from `wordpress:6.6-php8.3-fpm` and installs gd/imagick/mysqli.
- `docker-compose.yml` – WordPress (php-fpm), nginx, optional MariaDB profile.
- `nginx/default.conf` – nginx config inside the stack.
- `.env.template` / `.env.example` – copy to `.env` and fill DB creds/URLs.
- `data/` – hold `wp-content` persistence (empty; bring your content).
- `plugins.txt` – list of plugins to install via WP-CLI (slugs/URLs).
- `scripts/install-plugins.sh` – installs plugins from `plugins.txt` inside the WordPress container.

## Setup
1. Copy `.env.example` to `.env` and fill values from the current `wp-config.php`.
   - `WORDPRESS_DB_HOST`: if using the existing host DB, set to `host.docker.internal` (macOS/Windows) or the host IP. If you enable the bundled DB profile, set this to `db`.
   - `WORDPRESS_CONFIG_EXTRA`: optional `define('WP_HOME', ...)` / `define('WP_SITEURL', ...)` lines separated by `\n`.
2. Place your existing `wp-content` into `data/wp-content` (plugins, themes, uploads). WordPress core will be populated automatically into the `wordpress_data` volume on first run.
3. Back up DB + `data/wp-content` before switching traffic.

## Migrate an existing site
1. Back up first (recommended): copy `wp-content` and, if desired, take a DB backup.
   - Files: `rsync -avz user@host:/path/to/wp-content/ ./data/wp-content/` (trailing slashes preserve structure).
   - DB backup (optional): `mysqldump -u USER -p DBNAME > backup.sql`
2. Use the existing DB directly; no dump/restore is required to run the container. Fill `.env` using values from the current `wp-config.php`:
   - `WORDPRESS_DB_HOST`, `WORDPRESS_DB_NAME`, `WORDPRESS_DB_USER`, `WORDPRESS_DB_PASSWORD`, `WORDPRESS_TABLE_PREFIX`.
   - Add salts/keys via env (optional but preferred): `WORDPRESS_AUTH_KEY`, `WORDPRESS_SECURE_AUTH_KEY`, etc., or set them in `WORDPRESS_CONFIG_EXTRA`.
   - Add `WP_HOME`/`WP_SITEURL` in `WORDPRESS_CONFIG_EXTRA` if not already set.
3. Ensure the DB is reachable from Docker:
   - If DB stays on the host, set `WORDPRESS_DB_HOST=host.docker.internal` (macOS/Windows) or the host IP; confirm MySQL listens on that interface and firewall allows it.
4. Start the stack: `docker compose up -d --build` (no `with-db` profile when using the existing DB).
5. Point host nginx to `http://127.0.0.1:8081` and reload nginx. Verify site before decommissioning the old PHP runtime.

## Plugins via WP-CLI (plugins directory is not tracked)
- `data/wp-content/plugins` is ignored in git; repopulate plugins with WP-CLI using the included list/script.
- Edit `plugins.txt` to list slugs (or URLs for premium zips). Comments start with `#`.
- Install/activate all listed plugins:
  ```
  docker compose run --rm wordpress wp plugin install $(grep -Ev '^(#|$)' plugins.txt) --activate
  ```
  Or run the helper script:
  ```
  ./scripts/install-plugins.sh
  ```
  For premium/custom plugins, add a zip URL/file path to `plugins.txt` or install manually inside the container with `wp plugin install /path/to/plugin.zip --activate`.


## Run
- Without container DB (uses external DB):
  ```
  docker compose up -d --build
  ```
- With bundled MariaDB (for testing/migration):
  ```
  docker compose --profile with-db up -d --build
  ```

Services:
- `wordpress` (php-fpm): exposes 9000 internally to nginx; shares `wordpress_data` + `data/wp-content`.
- `nginx`: serves HTTP on 8081; mount the same files and forwards PHP to `wordpress`.
- `db` (profile `with-db`): MariaDB 10.11 with data in the `db_data` volume.

Stop stack: `docker compose down` (add `--volumes` only if you also want to drop DB/core volumes).

## Host nginx proxy (TLS stays on host)
Point the host vhost to the container nginx (`http://127.0.0.1:8081`):
```
server {
    listen 80;
    listen 443 ssl;
    server_name askmortgageauthority.com www.askmortgageauthority.com;
    # ssl_certificate /path/to/fullchain.pem;
    # ssl_certificate_key /path/to/privkey.pem;

    location / {
        proxy_pass http://127.0.0.1:8081;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }
}
```
Reload host nginx after updating the vhost.

## Notes
- PHP extensions included: gd (jpeg/freetype/webp), imagick, mysqli.
- `data/wp-content` is bind-mounted for persistence and to keep custom plugins/themes outside the image.
- WordPress core + plugins/themes are in `wordpress_data`; back it up before upgrades.
- If the DB is on the host, ensure it listens on an address reachable from Docker (e.g., `0.0.0.0` with firewall rules) and use `host.docker.internal`/host IP.
- To tail logs: `docker compose logs -f` (add service name to scope).
- WP-CLI is baked into the image; install/activate plugins as part of build/CI or post-deploy:
  ```
  docker compose run --rm wordpress wp plugin install akismet --activate
  docker compose run --rm wordpress wp plugin install jetpack --activate
  ```
  For premium plugins, supply the zip URL/file path instead of a slug. Ensure `.env` is set so WP-CLI can connect to your DB.
