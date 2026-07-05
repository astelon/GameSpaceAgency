#!/usr/bin/env bash
# Deploy webGame/ to a server over SSH (rsync). Run from anywhere:
#   webGame/tools/deploy.sh user@host:/var/www/html/spacerace [ssh_port]
#
# Examples:
#   webGame/tools/deploy.sh pi@192.168.100.100:/var/www/html/spacerace
#   webGame/tools/deploy.sh pi-lamp:/var/www/html/spacerace          # ~/.ssh/config alias
#   webGame/tools/deploy.sh u123456@your-domain.com:domains/x/public_html/spacerace 65002
#
# Credentials are never stored here — rsync uses your own SSH keys/agent.
set -euo pipefail

TARGET="${1:?usage: deploy.sh user@host:/path/to/webroot [ssh_port]}"
PORT="${2:-22}"
SRC="$(cd "$(dirname "$0")/.." && pwd)"

echo "Deploying $SRC → $TARGET (ssh port $PORT)"
rsync -avz --delete \
  -e "ssh -p $PORT" \
  --exclude 'api/data/*' \
  --exclude 'tools/' \
  --exclude '*.log' \
  "$SRC/" "$TARGET/"

# Make sure the runtime storage folder exists and is writable on the server.
HOSTPART="${TARGET%%:*}"
PATHPART="${TARGET#*:}"
ssh -p "$PORT" "$HOSTPART" "mkdir -p '$PATHPART/api/data' && chmod 775 '$PATHPART/api/data' && printf 'Require all denied\n' > '$PATHPART/api/data/.htaccess'"

echo "Done. Open the site and create a room to verify."
