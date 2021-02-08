FROM ubuntu:latest

ARG DEBIAN_FRONTEND="noninteractive"

RUN apt-get --yes --quiet update \
 && apt-get --yes --quiet install --no-install-recommends php-cli \
 && apt-get clean --yes --quiet \
 && rm --recursive --force /var/lib/apt/lists/*

COPY ["wait-for-it.php", "/usr/local/bin/wait-for-it.php"]

ENTRYPOINT ["/usr/local/bin/wait-for-it.php"]
