#!/usr/bin/env bash

set -e

# Fehler-Log-Datei
LOGFILE="/var/log/inventorio-install.log"
#exec 2>>"$LOGFILE"

# Optional: Alles loggen (stdout + stderr)
exec >>"$LOGFILE" 2>&1

# Root-Check
if [ "$EUID" -ne 0 ]; then
  echo "This script must be run as root. Please use sudo or switch to the root user."
  exit 1
fi

if [ -z "$1" ]; then
  echo "Usage: $0 <ID>"
  exit 1
fi

ID="$1"
PHAR_URL="https://github.com/startwind/inventorio-command-line/releases/latest/download/inventorio.phar"
PHAR_PATH="/usr/local/bin/inventorio"

if ! command -v php >/dev/null 2>&1; then
  echo "PHP is not installed. Please install PHP 7.4 or higher."
  exit 1
fi

PHP_VERSION=$(php -r 'echo PHP_VERSION;')
REQUIRED_VERSION="7.4"

version_ge() {
  [ "$(printf '%s\n' "$1" "$2" | sort -V | head -n1)" = "$2" ]
}

if ! version_ge "$PHP_VERSION" "$REQUIRED_VERSION"; then
  echo "PHP version $PHP_VERSION is too old. Please install PHP $REQUIRED_VERSION or higher."
  exit 1
fi

# Download die PHAR-Datei
echo "Downloading PHAR..."
curl -s -L "$PHAR_URL" -o "$PHAR_PATH"

# Mach sie ausführbar
chmod +x "$PHAR_PATH"

# Führe init mit der ID aus
echo "Initializing for user ID: $ID"
"$PHAR_PATH" init "$ID"

# Prüfe ob systemd vorhanden ist
if pidof systemd &>/dev/null; then
  echo "Setting up systemd service..."

  SERVICE_PATH="/etc/systemd/system/inventorio.service"
  cat <<EOF > "$SERVICE_PATH"
[Unit]
Description=Inventorio Daemon
After=network.target

[Service]
ExecStart=$PHAR_PATH daemon
Restart=always
User=$(logname)

[Install]
WantedBy=multi-user.target
EOF

  systemctl daemon-reexec
  systemctl daemon-reload
  systemctl enable --now inventorio.service

  echo "Service started via systemd."
else
  echo "systemd not found. Setting up a cron job..."

  CRON_SCRIPT="/usr/local/bin/inventorio-cron.sh"
  cat <<EOF > "$CRON_SCRIPT"
#!/bin/bash
while true; do
  $PHAR_PATH daemon
  echo "Inventorio daemon crashed or stopped. Restarting..."
  sleep 2
done
EOF

  chmod +x "$CRON_SCRIPT"

  (crontab -l 2>/dev/null; echo "@reboot /usr/local/bin/inventorio-cron.sh") | crontab -

  echo "Cron job added to restart the Inventorio daemon on startup."
fi
