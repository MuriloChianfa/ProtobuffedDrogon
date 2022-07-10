# From base drogon framework
FROM drogonframework/drogon

# Installs the libraries to enable compilation
RUN apt update && \
    apt install -y \
    protobuf-compiler \
    libprotobuf-dev \
    autoconf \
    automake \
    libtool \
    curl \
    make \
    g++ \
    unzip

# Download the protobuf librarie
RUN wget https://github.com/protocolbuffers/protobuf/releases/download/v3.11.0/protobuf-cpp-3.11.0.tar.gz

# Compiles them
RUN tar -xzvf protobuf-cpp-3.11.0.tar.gz
RUN cd protobuf-3.11.0 && \
    ./configure
RUN cd protobuf-3.11.0 && \
    make && \
    make install && \
    ldconfig

# Set workdir of project
RUN mkdir -p /drogon/app/build
WORKDIR /drogon/app/build

CMD cmake .. -DCMAKE_CXX_FLAGS='-pthread -lprotobuf -lpthread -std=c++20' && \
    make && \
    ./hello_world > /dev/stdout 2>&1
