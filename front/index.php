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
    <title>Interfaces</title>
</head>
<body>
    <div class="row">
        <div class="col-md-6 mt-4">
            <div id="bandwidth"></div>
        </div>
        <div class="col-md-6 mt-4">
            <div id="packets"></div>
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
            var dates = [];

            Date.prototype.addSegundos = function(segundos) {
                this.setSeconds(this.getSeconds() + segundos)
            };

            for (let index = 0; index < timeInterval; index++) {
                let date = new Date();
                date.addSegundos(-index);
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
            ['packets', 'bandwidth'].forEach(type => {
                var date = fakeNowDate();
                let traces = [];

                traces.push({
                    x: date,
                    y: Array(date.length).fill(0),
                    name: `RX - ${interface.name}`,
                    type: 'line',
                    fill: 'tozeroy',
                    line: {
                        color: 'rgb(0, 0, 0)',
                        width: 1
                    },
                });

                traces.push({
                    x: date,
                    y: Array(date.length).fill(0),
                    name: `TX - ${interface.name}`,
                    type: 'line',
                    fill: 'tozeroy',
                    line: {
                        color: 'rgb(100, 100, 100)',
                        width: 1
                    },
                });

                let chartContent = document.createElement('div');
                chartContent.id = `chart-${type}-${interface.name}`;
    
                document.getElementById(type).appendChild(chartContent);

                let exponent = {ticksuffix: 'PPS'};
                let title = `Packets per second (PPS)`;
                if (type === 'bandwidth') {
                    exponent = {
                        exponentformat: 'SI',
                        ticksuffix: 'bps'
                    };
                    title = `Bits per second (bps)`;
                }

                Plotly.newPlot(
                    document.querySelector(`#chart-${type}-${interface.name}`),
                    traces,
                    {
                        title: title,
                        showlegend: true,
                        height: 300,
                        legend: {
                            orientation: 'v'
                        },
                        yaxis: exponent,
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
                        displayModeBar: false,
                        paper_bgcolor: 'rgba(0, 0, 0, 0)',
                        plot_bgcolor: 'rgba(0, 0, 0, 0)'
                    }
                );
            });

            charts.push({
                name: interface.name,
                packets: {rx: interface.packets.rx ?? 0, tx: interface.packets.tx ?? 0},
                bandwidth: {rx: interface.bandwidth.rx ?? 0, tx: interface.bandwidth.tx ?? 0}
            });
        }

        function getNewDateRange() {
            return [
                new Date((new Date()).getTime() - (timeInterval * 1000)).toISOString().replace(/\.\d+/, ""),
                new Date().toISOString().replace(/\.\d+/, "")
            ];
        }

        function appendToCharts(interface) {
            let newDateRange = getNewDateRange();
            let chart = findChartByName(interface.name);

            updatePacketsChart(chart, interface, newDateRange);
            updateBandwidthChart(chart, interface, newDateRange);
        }

        function findChartByName(name) {
            return window.charts.find(chart => chart.name === name);
        }

        function updatePacketsChart(chart, interface, newDateRange) {
            let context = `chart-packets-${interface.name}`;

            // RX calc
            let rxValue = Math.abs(interface.packets.rx - chart.packets.rx);
            chart.packets.rx = interface.packets.rx;

            // TX calc
            let txValue = Math.abs(interface.packets.tx - chart.packets.tx);
            chart.packets.tx = interface.packets.tx;

            updateChart(context, newDateRange, rxValue, txValue, interface);
        }

        function updateBandwidthChart(chart, interface, newDateRange) {
            let context = `chart-bandwidth-${interface.name}`;

            // RX calc
            let rxValue = Math.abs(interface.bandwidth.rx - chart.bandwidth.rx);
            chart.bandwidth.rx = interface.bandwidth.rx;

            // TX calc
            let txValue = Math.abs(interface.bandwidth.tx - chart.bandwidth.tx);
            chart.bandwidth.tx = interface.bandwidth.tx;

            updateChart(context, newDateRange, rxValue, txValue);
        }

        function updateChart(context, newDateRange, rxValue, txValue) {
            Plotly.extendTraces(context, { y: [[Math.abs(rxValue)], [-Math.abs(txValue)]], x: [[newDateRange[1]], [newDateRange[1]]] }, [0, 1], 60);
            Plotly.relayout(context, {
                range: newDateRange
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

