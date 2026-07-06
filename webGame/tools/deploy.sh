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

# Make sure the runtime storage folder exists and is writable by the WEB
# SERVER user (often www-data inside a Docker LAMP container, while rsync
# writes as your ssh user). chgrp to www-data when possible; otherwise fall
# back to 777 — acceptable for a private test box, and the folder is blocked
# from direct download by its .htaccess.
HOSTPART="${TARGET%%:*}"
PATHPART="${TARGET#*:}"
ssh -p "$PORT" "$HOSTPART" "
  mkdir -p '$PATHPART/api/data' &&
  printf 'Require all denied\n' > '$PATHPART/api/data/.htaccess' &&
  { chgrp www-data '$PATHPART/api/data' 2>/dev/null && chmod 2775 '$PATHPART/api/data' \
    || chmod 777 '$PATHPART/api/data'; }
"

echo "Done."
echo "Verify the server:  http://<host>/<path>/api/index.php?op=health"
echo "Then open the game: http://<host>/<path>/"
