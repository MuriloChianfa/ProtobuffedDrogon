// Load a protobuf library
protobuf.load("protos/bundle.json", function(err, root) {
    if (err) throw err;

    // Set a protobuf object
    window.Interfaces = root.lookupType("sender.Interfaces"); 
});

// Convert binary protobuf message to Javascript object
function decodePayload(payload) {
    return Interfaces.decode(new Uint8Array(payload));
}
