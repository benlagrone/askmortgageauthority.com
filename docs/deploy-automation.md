# Host Fallback Deploy

The canonical production deploy path is the fortress control-plane workflow in
`fortress-phronesis`. This repo still includes a host-local fallback
orchestrator at `scripts/deploy.sh` for cases where you need to run the cutover
directly on the production host.

That script automates:

- live DB backups
- full live `wp-content` backups
- syncing the current live `uploads` directory into `data/uploads`
- `docker compose up -d --build`
- smoke testing the containerized site on port `18020`
- optional maintenance/freeze commands for the legacy site
- a final pre-cutover backup
- host nginx cutover to the container stack
- rollback back to the legacy stack

## Files

- `.deploy.env.example` – deploy-specific configuration; copy to `.deploy.env`
- `scripts/deploy.sh` – main host-fallback deploy entrypoint
- `docs/README.md` – base Docker/WordPress stack notes

## Assumption

The automation assumes the current live site is still on host nginx + a legacy PHP runtime, and that the Docker stack from this repo is the replacement target.

It also assumes the deployment runs on the host that currently serves production, because backups and cutover operate against:

- the host filesystem
- the host database connection
- the host nginx config

## One-Time Setup

1. Copy `.deploy.env.example` to `.deploy.env`.
2. Fill in the live `wp-content` path, live `uploads` path, live DB
   credentials, and host nginx paths.
3. Review the generated legacy/container nginx config assumptions.
4. Run:

   ```bash
   ./scripts/deploy.sh bootstrap-nginx
   ```

This creates two managed nginx configs:

- `<site>.legacy.conf`
- `<site>.container.conf`

and makes the legacy config the active one in `sites-enabled`.

## Recurring Deploy

Run:

```bash
./scripts/deploy.sh deploy
```

That performs this sequence:

1. Take a `pre-build` backup of:
   - live DB dump
   - live `wp-content`
   - current host nginx config state
2. Sync the current live `uploads` into `data/uploads`.
3. Rebuild and start the Docker stack.
4. Smoke test the containerized site on `127.0.0.1:18020`.
5. Optionally enable legacy maintenance mode if configured.
6. Take a `pre-cutover` backup.
7. Re-sync `uploads` for the final cutover window.
8. Switch host nginx to the managed container config.
9. Run post-cutover smoke tests.
10. Optionally run `POST_CUTOVER_CMD`.
11. Disable maintenance mode if configured.

## Rollback

Run:

```bash
./scripts/deploy.sh rollback
```

That switches host nginx back to the managed legacy config and runs the optional rollback hook.

If `AUTO_ROLLBACK_ON_FAILURE=1`, a failed deploy that already switched traffic will try to roll back automatically before exiting.

## Backups

Backups are written to `backups/<release>/` and include:

- `pre-build/live-db.sql.gz`
- `pre-build/live-wp-content.tar.gz`
- `pre-cutover/live-db.sql.gz`
- `pre-cutover/live-wp-content.tar.gz`
- nginx config snapshots for each stage

Standalone backup:

```bash
./scripts/deploy.sh backup-live
```

For a local smoke test when the production DB is not reachable from the machine
running the script, set `BACKUP_DB_ENABLED=0` in `.deploy.env`. That still tests
the file backup and manifest generation paths, but it is not a full production
backup validation.

## Status

Run:

```bash
./scripts/deploy.sh status
```

This prints:

- active deploy config
- active compose file
- backup/state paths
- currently active nginx target
- last recorded release metadata

## Notes

- The script trusts `.env` for the container stack and `.deploy.env` for
  host-side orchestration.
- For DB backups, `mysqldump` or `mariadb-dump` must be installed on the host.
- `HOST_NGINX_TEST_CMD` and `HOST_NGINX_RELOAD_CMD` are configurable so you can use `sudo`, `service nginx reload`, or another local convention.
- If your existing host nginx config has custom directives, use `HOST_NGINX_COMMON_EXTRA_FILE`, `HOST_NGINX_LEGACY_EXTRA_FILE`, or `HOST_NGINX_CONTAINER_EXTRA_FILE` to append them into the managed server blocks.
- The deploy script assumes the actual public cutover target is `18020`, which
  matches `docker-compose.yml`.
