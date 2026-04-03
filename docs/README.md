# AMA WordPress Source Overlay

This repo is now aligned to the fortress AMA WordPress deployment contract.
Production is expected to deploy through `fortress-phronesis`, which updates a
server checkout of this repo, runs a pre-deploy backup, rebuilds the WordPress
runtime, reinstalls WordPress.org plugins, and then runs smoke checks.

## Additional Docs

- `docs/deploy-automation.md` – host-fallback deploy and rollback workflow
- `docs/intake-prequalification-plan.md` – intake, prequalification, CRM, and funnel plan

## Contents
- `Dockerfile` – builds the WordPress PHP-FPM image with gd, imagick, mysqli, and WP-CLI.
- `docker-compose.yml` – local source-repo runtime using the same directory layout as prod.
- `nginx/default.conf` – nginx config inside the stack.
- `.env.example` – copy to `.env` and fill DB creds/URLs.
- `custom/wp-content/themes` – tracked themes shipped by the repo.
- `custom/wp-content/plugins` – bundled premium/private plugins that are not installed from WordPress.org.
- `custom/wp-content/mu-plugins` – tracked mu-plugins.
- `data/uploads` – persistent uploads/media.
- `plugins.txt` – WordPress.org plugins to install via WP-CLI after the container is running.
- `scripts/install-plugins.sh` – installs the `plugins.txt` list into the mounted plugins directory.

## Setup
1. Copy `.env.example` to `.env` and fill values from the current `wp-config.php`.
   - `WORDPRESS_DB_HOST`: use `host.docker.internal` when the DB stays on the host, or `db` if you enable the bundled DB profile.
   - `WORDPRESS_CONFIG_EXTRA`: optional `define('WP_HOME', ...)` / `define('WP_SITEURL', ...)` lines separated by `\n`.
2. Keep tracked code in `custom/wp-content/*` and persistent media in `data/uploads`.
3. Back up DB + `data/uploads` before switching traffic.

## Plugin Provisioning

- `custom/wp-content/plugins` is for bundled plugins that are private, premium, or otherwise not reinstallable from WordPress.org.
- `plugins.txt` is for public plugins that can be reinstalled deterministically on every deploy.
- Install the public plugin list into the mounted plugins directory with:

  ```bash
  ./scripts/install-plugins.sh
  ```


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
- `wordpress` (php-fpm): exposes 9000 internally to nginx; shares `wordpress_data`, `custom/wp-content/*`, and `data/uploads`.
- `nginx`: serves HTTP on 18020; mounts the same overlay directories and forwards PHP to `wordpress`.
- `db` (profile `with-db`): MariaDB 10.11 with data in the `db_data` volume.

Stop stack: `docker compose down` (add `--volumes` only if you also want to drop DB/core volumes).

## Host nginx proxy (TLS stays on host)
Point the host vhost to the container nginx (`http://127.0.0.1:18020` by default):
```
server {
    listen 80;
    listen 443 ssl;
    server_name askmortgageauthority.com www.askmortgageauthority.com;
    # ssl_certificate /path/to/fullchain.pem;
    # ssl_certificate_key /path/to/privkey.pem;

    location / {
        proxy_pass http://127.0.0.1:18020;
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
- `custom/wp-content/*` and `data/uploads` are bind-mounted for persistence and to keep source-managed code outside the image.
- WordPress core is in `wordpress_data`; back it up before upgrades.
- If the DB is on the host, ensure it listens on an address reachable from Docker and use `host.docker.internal`/host IP.
- To tail logs: `docker compose logs -f` (add service name to scope).
- WP-CLI is baked into the image, so the control-plane deploy can reinstall the public plugin list after the container starts.
