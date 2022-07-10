# Fixed PHP image
FROM php:8.1.0-cli

# Common labels
LABEL maintainer="Murilo Chianfa <github.com/murilochianfa>"
LABEL version="1.0.0"
LABEL version.is-production="no"
LABEL release-date="2022-07-10"
LABEL vendor="ProtobuffedDrogon"

# Expose default HTTP port
EXPOSE 80

# Set env vars
ENV DEBIAN_FRONTEND noninteractive
ENV NODE_VERSION 16.15.1
ENV ARCH x64

# Set default workdir to user
WORKDIR /app

# Installs the libraries to enable compilation
RUN apt update && \
    apt install -y \
    nodejs \
    npm

# Install the fixed verion of node and npm
RUN curl -fsSLO --compressed "https://nodejs.org/dist/v$NODE_VERSION/node-v$NODE_VERSION-linux-$ARCH.tar.xz" \
  && tar -xJf "node-v$NODE_VERSION-linux-$ARCH.tar.xz" -C /usr/local --strip-components=1 --no-same-owner \
  && rm "node-v$NODE_VERSION-linux-$ARCH.tar.xz" \
  && ln -s /usr/local/bin/node /usr/local/bin/nodejs \
  && node --version \
  && npm --version

# Install the protobuf compiler for JS
RUN npm install -g protobufjs-cli

# Init server on port 80
CMD pbjs --es6 --lint -t json /app/protos/*.proto > /app/protos/bundle.json && \
    php -S 0.0.0.0:80
