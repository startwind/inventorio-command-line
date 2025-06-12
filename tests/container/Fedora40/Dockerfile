# Verwende Fedora 40 als Basis
FROM fedora:40

# Deaktiviere interaktive Eingabe
ENV TERM=xterm

# Installiere benötigte Pakete
RUN dnf update -y && \
    dnf install -y php php-dom git wget curl unzip vim sudo logrotate && \
    dnf clean all

# Logfile erzeugen
RUN dd if=/dev/zero of=/var/log/inventorio.log bs=1M count=100

# Composer installieren
RUN curl -sS https://getcomposer.org/installer | php && \
    mv composer.phar /usr/local/bin/composer

# Repository klonen und Abhängigkeiten installieren
RUN git clone https://github.com/startwind/inventorio-command-line.git /var/tools/inventorio && \
    cd /var/tools/inventorio && \
    composer install || echo "Composer install failed"

# Konfigurationsdatei hinzufügen (falls vorhanden)
# COPY ../config.yml /root/.inventorio/config.yml

# Container am Leben halten
CMD ["tail", "-f", "/dev/null"]