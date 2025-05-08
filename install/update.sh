#!/bin/bash

set -e

# Version as first argument (optional)
VERSION="$1"

if [ -n "$VERSION" ]; then
    echo "Downloading inventorio.phar version $VERSION..."
    PHAR_URL="https://github.com/startwind/inventorio-command-line/releases/download/$VERSION/inventorio.phar"
else
    echo "Downloading latest inventorio.phar..."
    PHAR_URL="https://github.com/startwind/inventorio-command-line/releases/latest/download/inventorio.phar"
fi

# Temporary file for download
TMP_PHAR="/tmp/inventorio.phar"

# Download PHAR
curl -L "$PHAR_URL" -o "$TMP_PHAR"

# Find current installed path
echo "Searching for current 'inventorio' executable..."
INVENTORIO_PATH=$(which inventorio || true)

if [ -z "$INVENTORIO_PATH" ]; then
    echo "Error: 'inventorio' not found in PATH."
    exit 1
fi

echo "Found existing inventorio at: $INVENTORIO_PATH"
echo "Replacing with the new version..."

# Optional backup
cp -f "$INVENTORIO_PATH" "${INVENTORIO_PATH}.bak"

# Overwrite and set executable
cp -f "$TMP_PHAR" "$INVENTORIO_PATH"
chmod +x "$INVENTORIO_PATH"

# Check for systemd
echo "Checking for systemd..."
if pidof systemd &> /dev/null && [ -d /run/systemd/system ]; then
    echo "Systemd detected – trying to restart inventorio.service..."

    if systemctl list-units --type=service | grep -q "inventorio.service"; then
        echo "Restarting inventorio.service..."
        sudo systemctl restart inventorio.service
        echo "inventorio.service restarted successfully."
    else
        echo "Note: inventorio.service is not active or not found."
    fi
else
    echo "Systemd not detected – running 'inventorio collect' manually..."
    inventorio collect
fi

# Cleanup
echo "Cleaning up downloaded file..."
rm -f "$TMP_PHAR"

echo "Deleting update script..."
rm -- "$0"

echo "Update process completed."
