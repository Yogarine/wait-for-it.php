FROM ubuntu:latest

ARG DEBIAN_FRONTEND="noninteractive"

RUN apt-get --yes --quiet update \
 && apt-get --yes --quiet install --no-install-recommends php-cli \
 && apt-get clean --yes --quiet \
 && rm --recursive --force /var/lib/apt/lists/*

COPY ["bin/wait-for-it",     "/usr/local/bin/wait-for-it"]
COPY ["src/wait-for-it.php", "/usr/share/php/wait-for-it.php"]

ENTRYPOINT ["/usr/local/bin/wait-for-it"]
