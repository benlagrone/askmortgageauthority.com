#!/usr/bin/env bash
set -euo pipefail

# Install/activate WordPress plugins listed in plugins.txt using WP-CLI inside the WordPress container.
# Usage: ./scripts/install-plugins.sh [plugins-file]
# Defaults to ./plugins.txt (one slug or URL per non-empty line; lines starting with # are comments).

PLUGINS_FILE="${1:-./plugins.txt}"

if [[ ! -f "$PLUGINS_FILE" ]]; then
  echo "Plugins file not found: $PLUGINS_FILE" >&2
  exit 1
fi

while IFS= read -r line; do
  # Skip comments/blank lines
  [[ -z "$line" || "$line" =~ ^# ]] && continue
  PLUGIN="$line"
  echo "Installing/activating plugin: $PLUGIN"
  docker compose run --rm wordpress wp plugin install "$PLUGIN" --activate
done < "$PLUGINS_FILE"
