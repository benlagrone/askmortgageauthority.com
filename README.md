# AskMortgageAuthority.com Source Overlay

This repo is the source overlay for the containerized `askmortgageauthority.com`
WordPress runtime. The canonical production deploy path is the control-plane
workflow in `fortress-phronesis`, not the older host-local cutover script in
this repo.

## Source Layout

- `.env` for database and WordPress runtime config
- `Dockerfile` for the custom WordPress PHP-FPM image with required extensions
  and WP-CLI
- `nginx/default.conf` for the in-container nginx frontend
- `custom/wp-content/themes` for tracked theme code
- `custom/wp-content/plugins` for bundled premium/private plugins
- `custom/wp-content/mu-plugins` for tracked mu-plugins
- `data/uploads` for persistent media and upload state
- `plugins.txt` for WordPress.org plugins installed after the container starts

## Local Runtime

Bring the stack up locally:

```bash
docker compose up -d --build
```

The nginx container binds to `127.0.0.1:18020`. The compose file mounts the
overlay directories directly into `wp-content`, so changes under `custom/` and
`data/uploads` are reflected immediately.

Install WordPress.org plugins listed in `plugins.txt`:

```bash
./scripts/install-plugins.sh
```

Premium or private plugins that cannot be reinstalled from WordPress.org belong
under `custom/wp-content/plugins`.

## Production Deploy

The fortress control-plane deploy contract lives in:

- `fortress-phronesis/docs/ama-wordpress-deployment.md`
- `fortress-phronesis/.github/workflows/deploy-ama-wordpress.yml`

That path is backup-first:

1. Back up DB, uploads, env, and git state on the server.
2. Update the server checkout of this repo.
3. Rebuild or refresh the WordPress runtime.
4. Reinstall the WordPress.org plugins from `plugins.txt`.
5. Run local and public smoke checks.
