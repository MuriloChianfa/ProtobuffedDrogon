// Connect to drogon WebSocket
const ws = new WebSocket('ws://127.0.0.1:8848/interfaces');

// Set default WebSocket communication protocol to binary
ws.binaryType = 'arraybuffer';

// Listening the websocket for new packets
ws.addEventListener('message', message => {
    let payload = decodePayload(message.data);

    // Create charts if not exists
    if (charts.length === 0) {
        iterateThroughtInterfaces(payload, createCharts);
        return;
    }

    iterateThroughtInterfaces(payload, appendToCharts);
});

// Iterage throught all interfaces and execute the callback
function iterateThroughtInterfaces(interfaces, callback) {
    Object.values(interfaces).forEach(interface => {
        interface.forEach(trace => {
            callback?.(trace);
        });
    });
}

// Show error message if not can connect with websocket
const wsHandleErrors = function (error) {
    Toast.fire({
        icon: 'error',
        title: 'Ocorreu algum erro ao se comunicar com o servidor de WebSocket!'
    });
};

// Set error messages
ws.onerror = wsHandleErrors;
ws.onclose = wsHandleErrors;

// Show success message if connect succefully
ws.addEventListener('open', () => {
    Toast.fire({
        icon: 'success',
        title: 'Conexão com o servidor de WebSocket efetuada com sucesso!'
    });
});
