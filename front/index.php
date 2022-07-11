<?php declare(strict_types=1); ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js" integrity="sha256-/xUj+3OJU5yExlq6GSYGSHk7tPXikynS7ogEvDej/m4=" crossorigin="anonymous"></script>
    <script src="https://cdn.plot.ly/plotly-latest.js"></script>
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
        <div class="col-md-12 mt-4">
            <div id="content"></div>
        </div>
    </div>

    <script>
        var Interfaces;
        var charts = [];
        const timeInterval = 180;

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

        function fakeNowDate() {
            Date.prototype.addHoras = function(horas){
                this.setHours(this.getHours() + horas)
            };
            Date.prototype.addMinutos = function(minutos){
                this.setMinutes(this.getMinutes() + minutos)
            };
            Date.prototype.addSegundos = function(segundos){
                this.setSeconds(this.getSeconds() + segundos)
            };
            Date.prototype.addDias = function(dias){
                this.setDate(this.getDate() + dias)
            };
            Date.prototype.addMeses = function(meses){
                this.setMonth(this.getMonth() + meses)
            };
            Date.prototype.addAnos = function(anos){
                this.setYear(this.getFullYear() + anos)
            };

            var dates = [];

            for (let index = 0; index < timeInterval; index++) {
                let date = new Date();
                date.setSeconds(date.getSeconds() - index);
                dates.push(date.toISOString().replace(/\.\d+/, ""));
            }

            return dates;
        }

        ws.addEventListener('message', message => {
            // Convert binary protobuf message to Javascript object
            let payload = Interfaces.decode(new Uint8Array(message.data));

            if (charts.length === 0) {
                Object.values(payload).forEach(interface => {
                    interface.forEach(trace => {
                        createCharts(trace);
                    });
                });
            }

            Object.values(payload).forEach(interface => {
                interface.forEach(trace => {
                    appendToCharts(trace);
                });
            });
        });

        function createCharts(interface) {
            var traces = [];
            var date = fakeNowDate();

            traces.push({
                x: date,
                y: Array(date.length).fill(0),
                name: `${interface.name} - ${interface.addresses[0].address}`,
                type: 'line'
            });

            let chartContent = document.createElement('div');
            chartContent.id = `chart${interface.name}`;

            document.getElementById('content').appendChild(chartContent);

            let chart = Plotly.newPlot(
                document.querySelector(`#chart${interface.name}`),
                traces,
                {
                    title: 'Traffic of interfaces',
                    showlegend: true,
                    height: 250,
                    legend: {
                        orientation: 'v'
                    },
                    hovermode: 'closest',
                    barmode: 'relative',
                    margin: {
                        l: 50,
                        r: 5,
                        b: 40,
                        t: 50
                    },
                },
                {
                    responsive: true,
                    displayModeBar: false
                }
            );

            let interfaceName = interface.name;
            charts.push({interfaceName: interface.packets.tx});
        }

        function getNewDateRange() {
            return [
                new Date((new Date()).getTime() - (timeInterval * 1000)).toISOString().replace(/\.\d+/, ""),
                new Date().toISOString().replace(/\.\d+/, "")
            ];
        }

        function appendToCharts(interface) {
            var newDateRange = getNewDateRange();
            var chartContext = document.getElementById(`chart${interface.name}`);
            var value = Math.abs(charts[interface.name] - interface.packets.tx);
            charts[interface.name] = interface.packets.tx;

            Plotly.extendTraces(chartContext, { y: [[value == 1 ? 0 : value]], x: [[newDateRange[1]]] }, [0]);
            Plotly.relayout(chartContext, {
                xaxis: {
                    rangeslider: {
                        thickness: 0.08,
                        autorange: true
                    },
                    range: newDateRange
                }
            });
        }

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

