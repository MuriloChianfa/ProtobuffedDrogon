const timeInterval = 300;
var charts = [];

function createTrace(date, name, color) {
    return {
        x: date,
        y: Array(date.length).fill(0),
        name: name,
        type: 'line',
        fill: 'tozeroy',
        line: {color: color, width: 1},
    };
}

function createChart(interface, type) {
    let id = `chart-${type}-${interface.name}`;
    let date = fakeNowDate();
    let traces = [];

    traces.push(createTrace(date, `RX - ${interface.name}`, 'rgb(0, 0, 0)'));
    traces.push(createTrace(date, `TX - ${interface.name}`, 'rgb(100, 100, 100)'));

    let chartContent = document.createElement('div');
    chartContent.id = id;

    document.getElementById(type).appendChild(chartContent);

    let exponent = {ticksuffix: 'PPS'};
    let title = `Packets per second (PPS)`;
    
    if (type === 'bandwidth') {
        exponent = {exponentformat: 'SI', ticksuffix: 'bps'};
        title = `Bits per second (bps)`;
    }

    let options = {responsive: true, displayModeBar: false};
    let style = {
        title: title,
        showlegend: true,
        height: 300,
        legend: {orientation: 'v'},
        hovermode: 'closest',
        barmode: 'relative',
        yaxis: exponent,
        xaxis: {
            autorange: true
        },
        margin: {l: 50, r: 5, b: 40, t: 50},
    };

    let context = document.getElementById(id);
    Plotly.newPlot(context, traces, style, options);

    // Fix to 0 the inital trace value
    updateChart(context, date, 0, 0, interface);
}

function createChartObject(interface) {
    return {
        name: interface.name,
        packets: {rx: interface.packets.rx ?? 0, tx: interface.packets.tx ?? 0},
        bandwidth: {rx: interface.bandwidth.rx ?? 0, tx: interface.bandwidth.tx ?? 0}
    };
}

function createCharts(interface) {
    ['packets', 'bandwidth'].forEach(type => {
        createChart(interface, type);
    });

    charts.push(createChartObject(interface));
}

function appendToCharts(interface) {
    let newDateRange = fakeNowDate();
    let chart = window.charts.find(chart => chart.name === interface.name);

    updatePacketsChart(chart, interface, newDateRange);
    updateBandwidthChart(chart, interface, newDateRange);
}

function updatePacketsChart(chart, interface, newDateRange) {
    let context = `chart-packets-${interface.name}`;

    let rxValue = Math.abs(interface.packets.rx - chart.packets.rx);
    chart.packets.rx = interface.packets.rx;

    let txValue = Math.abs(interface.packets.tx - chart.packets.tx);
    chart.packets.tx = interface.packets.tx;

    updateChart(context, newDateRange, rxValue, txValue);
}

function updateBandwidthChart(chart, interface, newDateRange) {
    let context = `chart-bandwidth-${interface.name}`;

    let rxValue = Math.abs(interface.bandwidth.rx - chart.bandwidth.rx);
    chart.bandwidth.rx = interface.bandwidth.rx;

    let txValue = Math.abs(interface.bandwidth.tx - chart.bandwidth.tx);
    chart.bandwidth.tx = interface.bandwidth.tx;

    updateChart(context, newDateRange, rxValue, txValue);
}

function updateChart(context, newDateRange, rxValue, txValue) {
    Plotly.extendTraces(context, { y: [[Math.abs(rxValue)], [-Math.abs(txValue)]], x: [[newDateRange[1]], [newDateRange[1]]] }, [0, 1], timeInterval);
    Plotly.relayout(context, {range: newDateRange});
}
