#!/bin/bash

set -e

# Farben
GREEN='\033[0;32m'
RED='\033[0;31m'
NC='\033[0m'

# Spinner-Progressbar
start_progress_bar() {
    local msg="$1"
    echo -e "${GREEN} $msg${NC}"
    (
        i=0
        while true; do
            local spin='-\|/'
            local char="${spin:i++%${#spin}:1}"
            echo -ne "   [${char}] Working \r"
            sleep 0.1
        done
    ) &
    PROGRESS_PID=$!
}

stop_progress_bar() {
    kill "$PROGRESS_PID" &>/dev/null || true
    wait "$PROGRESS_PID" 2>/dev/null || true
    echo -ne "\r [✔] Done.          \n"
    echo ""
}

echo -e " Starting Inventorio update process "
echo ""

# 1. Version prüfen
VERSION="$1"
if [ -n "$VERSION" ]; then
    PHAR_URL="https://github.com/startwind/inventorio-command-line/releases/download/$VERSION/inventorio.phar"
    echo -e "${GREEN} Selected version: $VERSION${NC}"
else
    PHAR_URL="https://github.com/startwind/inventorio-command-line/releases/latest/download/inventorio.phar"
    echo -e "${GREEN} Using latest version${NC}"
fi

echo ""

TMP_PHAR="/tmp/inventorio.phar"

# 2. PHAR herunterladen
start_progress_bar "Downloading inventorio.phar "
curl -sSL "$PHAR_URL" -o "$TMP_PHAR"
stop_progress_bar

# 3. Vorhandene Binärdatei finden
start_progress_bar "Searching for current 'inventorio' executable "
INVENTORIO_PATH=$(which inventorio || true)
stop_progress_bar

if [ -z "$INVENTORIO_PATH" ]; then
    echo -e "${RED}Error: 'inventorio' not found in PATH.${NC}"
    exit 1
fi

# echo -e "${GREEN}Found at: $INVENTORIO_PATH${NC}"

# 4. Backup der alten Version
start_progress_bar "Backing up existing version"
cp -f "$INVENTORIO_PATH" "${INVENTORIO_PATH}.bak"
stop_progress_bar

# 5. Neue Version kopieren
start_progress_bar "Replacing with new version"
cp -f "$TMP_PHAR" "$INVENTORIO_PATH"
chmod +x "$INVENTORIO_PATH"
stop_progress_bar

# 6. Systemd prüfen & Dienst neustarten
start_progress_bar "Checking for systemd"
HAS_SYSTEMD=false
if pidof systemd &> /dev/null && [ -d /run/systemd/system ]; then
    HAS_SYSTEMD=true
fi
stop_progress_bar

if $HAS_SYSTEMD; then
    start_progress_bar "Checking for inventorio.service"
    if systemctl list-units --type=service | grep -q "inventorio.service"; then
        stop_progress_bar
        start_progress_bar "Restarting inventorio.service"
        sudo systemctl restart inventorio.service
        stop_progress_bar
        # echo -e "${GREEN}Service restarted successfully.${NC}"
    else
        stop_progress_bar
        echo -e "${RED}inventorio.service not active or not found.${NC}"
    fi
else
    start_progress_bar "Systemd not detected – running 'inventorio collect'"
    inventorio collect
    stop_progress_bar
fi

# 7. Aufräumen
start_progress_bar "Cleaning up temporary files"
rm -f "$TMP_PHAR"
stop_progress_bar

# 8. Script löschen
start_progress_bar "Deleting update script"
rm -- "$0"
stop_progress_bar

echo ""
echo ""
echo -e "${GREEN} Update process completed successfully.${NC}"