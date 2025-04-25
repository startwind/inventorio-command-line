#!/bin/bash

set -e

# URL zum Download
PHAR_URL="https://github.com/startwind/inventorio-command-line/releases/latest/download/inventorio.phar"

# Temporäre Datei für den Download
TMP_PHAR="/tmp/inventorio.phar"

echo "Lade neue inventorio.phar herunter..."
curl -L "$PHAR_URL" -o "$TMP_PHAR"

# Stelle sicher, dass das Script existiert
echo "Suche nach dem aktuellen installierten inventorio..."
INVENTORIO_PATH=$(which inventorio || true)

if [ -z "$INVENTORIO_PATH" ]; then
    echo "Fehler: 'inventorio' wurde nicht gefunden im Pfad."
    exit 1
fi

echo "Aktuelles inventorio gefunden unter: $INVENTORIO_PATH"
echo "Überschreibe mit neuer Version..."

# Backup optional
cp "$INVENTORIO_PATH" "${INVENTORIO_PATH}.bak"

# Neue Datei kopieren
cp "$TMP_PHAR" "$INVENTORIO_PATH"
chmod +x "$INVENTORIO_PATH"

echo "Neustart des inventorio.service falls systemd verwendet wird..."

# Prüfe ob systemd vorhanden ist
if pidof systemd &> /dev/null && [ -d /run/systemd/system ]; then
    if systemctl list-units --type=service | grep -q "inventorio.service"; then
        echo "Starte inventorio.service neu..."
        sudo systemctl restart inventorio.service
        echo "inventorio.service wurde neu gestartet."
    else
        echo "Hinweis: inventorio.service ist nicht aktiv oder nicht vorhanden."
    fi
else
    echo "Systemd ist nicht verfügbar – überspringe Dienstneustart."
fi

echo "Update abgeschlossen."
