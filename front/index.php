<?php declare(strict_types=1); ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js" integrity="sha256-/xUj+3OJU5yExlq6GSYGSHk7tPXikynS7ogEvDej/m4=" crossorigin="anonymous"></script>
    <script src="https://cdn.plot.ly/plotly-latest.min.js"></script>
    <script src="http://d3js.org/d3.v3.min.js" charset="utf-8"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.min.js" integrity="sha384-QJHtvGhmr9XOIpI6YVutG+2QOK9T+ZnN4kzFN1RtK3zEFEIsxhlmWl5/YESvpZ13" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ka7Sk0Gln4gmtz2MlQnikT1wXgYsOg+OMhuP+IlRH9sENBO0LRn5q+8nbTov4+1p" crossorigin="anonymous"></script>
    <script src="//cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="//cdn.rawgit.com/dcodeIO/protobuf.js/6.X.X/dist/protobuf.min.js"></script>
    <link rel="shortcut icon" href="favicon.ico" type="image/x-icon">
    <title>test</title>
</head>
<body>
    <div class="row">
        <div class="col-md-4 offset-md-4 mt-4">
            <div id="text"></div>
        </div>
    </div>

    <script>
        var Interfaces;

        // Load a protobuf library
		protobuf.load("protos/bundle.json", function(err, root) {
			if (err) throw err;

			// Set a protobuf object
			window.Interfaces = root.lookupType("sender.Interfaces"); 
        });

        // Connect to drogon WebSocket
        const ws = new WebSocket('ws://127.0.0.1:8848/interfaces');

        // Set default WebSocket communication protocol to binary
        ws.binaryType = 'arraybuffer';

        ws.addEventListener('message', message => {
            // Convert binary protobuf message to Javascript object
            let payload = Interfaces.decode(new Uint8Array(message.data));

			console.log(payload);
        });

        // Send request to WebSocket
        function send() {
            // Init benchmark of time to request
            // console.time('timeToRequest');

            // Payload to sent
			// var payload = { id: 1, name: 'test' };

			// Create a new message
			// var message = window.Interfaces.create(payload);
			// var buffer = window.Interfaces.encode(message).finish();

            // ws.send(buffer);
        }

        const Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true,
            didOpen: (toast) => {
                toast.addEventListener('mouseenter', Swal.stopTimer);
                toast.addEventListener('mouseleave', Swal.resumeTimer);
            }
        });

        const wsHandleErrors = function (error) {
            Toast.fire({
                icon: 'error',
                title: 'Ocorreu algum erro ao se comunicar com o servidor de WebSocket!'
            });
        };

        ws.onerror = wsHandleErrors;
        ws.onclose = wsHandleErrors;

        ws.addEventListener('open', () => {
            Toast.fire({
                icon: 'success',
                title: 'Conexão com o servidor de WebSocket efetuada com sucesso!'
            });
        });
        </script>
</body>
</html>

