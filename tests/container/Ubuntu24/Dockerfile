# Use the official Ubuntu 24.04 as the base image
FROM ubuntu:24.04

ENV DEBIAN_FRONTEND=noninteractive

RUN apt-get update && \
    apt-get install -y wget php php-dom git vim sudo logrotate && \
    apt-get clean

# Create a 100MB log file
RUN dd if=/dev/zero of=/var/log/inventorio.log bs=1M count=100

# Download and run the install script, but don't fail if it breaks
#RUN wget -q -N "https://github.com/startwind/inventorio-command-line/releases/latest/download/install.sh" || echo "Download fehlgeschlagen" && \
#    bash install.sh 1f019c5e-3afe-65d8-8e0c-97ec04579c1a metrics=on || echo "Installation fehlgeschlagen"

# Install PHP, wget, git, and composer
RUN apt-get update && \
    apt-get install -y wget php git curl unzip && \
    curl -sS https://getcomposer.org/installer | php && \
    mv composer.phar /usr/local/bin/composer && \
    apt-get clean

# Clone the repository and install dependencies
RUN git clone https://github.com/startwind/inventorio-command-line.git /var/tools/inventorio && \
    cd /var/tools/inventorio && \
    composer install || echo "Composer install failed"

COPY config.yml /root/.inventorio/config.yml

# Keep the container running
CMD ["tail", "-f", "/dev/null"]

# commands
# rm /var/log/inventorio.log.1.gz; rm /etc/logrotate.d/inventorio; dd if=/dev/zero of=/var/log/inventorio.log bs=1M count=100