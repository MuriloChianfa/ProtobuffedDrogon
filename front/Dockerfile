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

# Set default workdir to user
WORKDIR /app

# Init server on port 80
CMD php -S 0.0.0.0:80

