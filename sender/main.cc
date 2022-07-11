#include <drogon/WebSocketController.h>
#include <drogon/PubSubService.h>
#include <drogon/HttpAppFramework.h>

#include <protos/interfaces.pb.h>

#include <google/protobuf/io/zero_copy_stream_impl.h>
#include <google/protobuf/io/coded_stream.h>
#include <google/protobuf/message_lite.h>

#include <unistd.h>
#include <iostream>
#include <thread>
#include <chrono>

#include <netinet/in.h>
#include <arpa/inet.h>
#include <sys/socket.h>
#include <netdb.h>
#include <ifaddrs.h>
#include <stdlib.h>
#include <linux/if_link.h>

using std::cout;
using std::endl;
using namespace std::chrono;
using namespace std::chrono_literals;

using namespace google::protobuf::io;
using namespace google::protobuf;

using namespace drogon;
using namespace drogon::orm;
using namespace drogon::nosql;

static WebSocketConnectionPtr client;

class WebSocket : public drogon::WebSocketController<WebSocket>
{
  public:
    virtual void handleNewConnection(const HttpRequestPtr &, const WebSocketConnectionPtr &) override;
    virtual void handleConnectionClosed(const WebSocketConnectionPtr &) override;
    virtual void handleNewMessage(const WebSocketConnectionPtr &, std::string &&, const WebSocketMessageType &) override;
    static void sendInterfaces();

    WS_PATH_LIST_BEGIN
    WS_PATH_ADD("/interfaces", Get);
    WS_PATH_LIST_END
};

void WebSocket::handleNewConnection(const HttpRequestPtr &req, const WebSocketConnectionPtr &conn)
{
    conn->setPingMessage("", 30s);
    client = conn;

    LOG_DEBUG << conn->localAddr().toIp() << " - Has connected.";
}

void WebSocket::handleConnectionClosed(const WebSocketConnectionPtr &conn)
{
    client = nullptr;
    LOG_DEBUG << conn->localAddr().toIp() << " - Has disconnected.";
}

void WebSocket::handleNewMessage(const WebSocketConnectionPtr &wsConnPtr, std::string &&message, const WebSocketMessageType &type)
{
    // LOG_DEBUG << "Received message with type: [" << static_cast<int>(type) << "]";
}

void WebSocket::sendInterfaces()
{
    int i = 0;

    sender::Interfaces interfaces;
    std::string payload;

    struct ifaddrs *ifaddr;
    int family, s;
    char host[NI_MAXHOST];

    if (getifaddrs(&ifaddr) == -1) {
       perror("getifaddrs");
       exit(EXIT_FAILURE);
    }

    for (struct ifaddrs *ifa = ifaddr; ifa != NULL; ifa = ifa->ifa_next) {
        if (ifa->ifa_addr == NULL || ifa->ifa_addr->sa_family == AF_PACKET) {
            continue;
        }

        family = ifa->ifa_addr->sa_family;
        sender::Interface* interface = interfaces.add_interfaces();
        interface->set_name(ifa->ifa_name);

        LOG_DEBUG << "Interface discovered: " << ifa->ifa_name;

        s = getnameinfo(ifa->ifa_addr,
                (family == AF_INET) ? sizeof(struct sockaddr_in) :
                                        sizeof(struct sockaddr_in6),
                host, NI_MAXHOST,
                NULL, 0, NI_NUMERICHOST);
        if (s != 0) {
            printf("getnameinfo() failed: %s\n", gai_strerror(s));
            exit(EXIT_FAILURE);
        }

        sender::IPAddress* address = interface->add_addresses();
        address->set_type(
            (family == AF_INET) ? "4" :
            (family == AF_INET6) ? "6" : "4"
        );
        address->set_address(host);
    }

    while (true)
    {
        if (client == nullptr)
        {
            std::this_thread::sleep_for(std::chrono::milliseconds(1000));
            continue;
        }

        // Get values
        getifaddrs(&ifaddr);
        for (struct ifaddrs *ifa = ifaddr; ifa != NULL; ifa = ifa->ifa_next) {
            if (ifa->ifa_addr == NULL || ifa->ifa_addr->sa_family != AF_PACKET || ifa->ifa_data == NULL) {
                continue;
            }

            for (int i = 0; i < interfaces.interfaces_size(); i++) {
                if (ifa->ifa_name != interfaces.mutable_interfaces(i)->name()) {
                    continue;
                }

                struct rtnl_link_stats *stats = static_cast<rtnl_link_stats*>(ifa->ifa_data);

                interfaces.mutable_interfaces(i)->mutable_bandwidth()->set_rx(stats->rx_bytes);
                interfaces.mutable_interfaces(i)->mutable_bandwidth()->set_tx(stats->tx_bytes);
                interfaces.mutable_interfaces(i)->mutable_packets()->set_rx(stats->rx_packets);
                interfaces.mutable_interfaces(i)->mutable_packets()->set_tx(stats->tx_packets);
            }
        }

        interfaces.AppendToString(&payload);

        client->send(payload, WebSocketMessageType::Binary);
        payload.clear();
        i++;

        std::this_thread::sleep_for(std::chrono::milliseconds(1000));
    }

    freeifaddrs(ifaddr);
}

int main()
{
    std::thread t1(WebSocket::sendInterfaces);

    drogon::app().loadConfigFile("../config.json");
    drogon::app().addListener("0.0.0.0", 8848);
    drogon::app().run();

    t1.join();

    return 0;
}

