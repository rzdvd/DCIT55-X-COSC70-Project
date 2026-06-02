#!/bin/bash
# Copies this project into XAMPP htdocs so Apache can serve it.
set -e
SRC="$(cd "$(dirname "$0")" && pwd)"
DEST="/Applications/XAMPP/xamppfiles/htdocs/hanapdorm"
mkdir -p "$DEST"
rsync -a --delete \
  --exclude '.git' \
  --exclude 'sync-to-xampp.sh' \
  "$SRC/" "$DEST/"
echo "Synced to $DEST"
echo "Open: http://localhost/hanapdorm2/homepage.html"


echo "Open: http://localhost/hanapdorm2/auth/reset-password.php?token=<generated-token>"