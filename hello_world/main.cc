#include <drogon/WebSocketController.h>
#include <drogon/PubSubService.h>
#include <drogon/HttpAppFramework.h>

#include <protos/chart.pb.h>

#include <google/protobuf/io/zero_copy_stream_impl.h>
#include <google/protobuf/io/coded_stream.h>
#include <google/protobuf/message_lite.h>

#include <unistd.h>
#include <iostream>

using std::cout;
using std::endl;
using namespace std::chrono_literals;

using namespace google::protobuf::io;
using namespace google::protobuf;

using namespace drogon;
using namespace drogon::orm;
using namespace drogon::nosql;

class WebSocketChat : public drogon::WebSocketController<WebSocketChat>
{
  public:
    virtual void handleNewMessage(const WebSocketConnectionPtr &,
                                  std::string &&,
                                  const WebSocketMessageType &) override;
    virtual void handleConnectionClosed(
        const WebSocketConnectionPtr &) override;
    virtual void handleNewConnection(const HttpRequestPtr &,
                                     const WebSocketConnectionPtr &) override;
    WS_PATH_LIST_BEGIN
    WS_PATH_ADD("/test", Get);
    WS_PATH_LIST_END
};

void WebSocketChat::handleNewMessage(const WebSocketConnectionPtr &wsConnPtr,
                                     std::string &&message,
                                     const WebSocketMessageType &type)
{
    teste::Chart c;
    std::string payload;
    int i = 0;

    LOG_DEBUG << "Received message with type: [" << static_cast<int>(type) << "]";

    switch (type)
    {
        case WebSocketMessageType::Ping:
            LOG_DEBUG << "WebSocketMessageType::Ping";
            break;

        case WebSocketMessageType::Pong:
            LOG_DEBUG << "WebSocketMessageType::Pong";
            break;

        case WebSocketMessageType::Text:
            LOG_DEBUG << "WebSocketMessageType::Text";
            break;

        case WebSocketMessageType::Binary:
            LOG_DEBUG << "WebSocketMessageType::Binary";

            // load sended object
            c.ParseFromString(message);

            // c.set_id(1);
            // c.set_name("test");

            c.AppendToString(&payload);

            wsConnPtr->send(payload, WebSocketMessageType::Binary);
            break;

        case WebSocketMessageType::Close:
            LOG_DEBUG << "WebSocketMessageType::Close";
            break;

        case WebSocketMessageType::Unknown:
            LOG_DEBUG << "WebSocketMessageType::Unknown";
            break;

        default:
            LOG_DEBUG << "WebSocketMessageType::Unrecognized";
            break;
    }
}

void WebSocketChat::handleConnectionClosed(const WebSocketConnectionPtr &conn)
{
    LOG_DEBUG << conn->localAddr().toIp() << " - Has disconnected.";
}

void WebSocketChat::handleNewConnection(const HttpRequestPtr &req,
                                        const WebSocketConnectionPtr &conn)
{
    conn->setPingMessage("", 30s);

    LOG_DEBUG << conn->localAddr().toIp() << " - Has connected.";
}

int main()
{
    drogon::app().loadConfigFile("../config.json");
    drogon::app().addListener("0.0.0.0", 8848);
    drogon::app().run();

    return 0;
}

