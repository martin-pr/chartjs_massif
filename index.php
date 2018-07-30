<?php
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
?>

<html>

<head>
    <title>Line Chart</title>
    <script src="Chart.bundle.js"></script>
    <style>
        canvas {
            -moz-user-select: none;
            -webkit-user-select: none;
            -ms-user-select: none;
        }
    </style>
</head>

<body>

<div style="width:75%;">
    <canvas id="canvas"></canvas>
</div>

<?php

$file = fopen('massif.out.5055', 'r');
$snapshots = array();

while (($line = fgets($file)) !== false) {
    if(preg_match('/^snapshot=([0-9]+)$/', $line, $matches))
        array_push($snapshots, array());

    else if(preg_match('/^time=([0-9]+)$/', $line, $matches))
        $snapshots[count($snapshots)-1]['time'] = $matches[1];

    else if(preg_match('/^mem_heap_B=([0-9]+)$/', $line, $matches))
        $snapshots[count($snapshots)-1]['mem_heap_B'] = $matches[1];

    else if(preg_match('/^mem_heap_extra_B=([0-9]+)$/', $line, $matches))
        $snapshots[count($snapshots)-1]['mem_heap_extra_B'] = $matches[1];

    else if(preg_match('/^mem_stacks_B=([0-9]+)$/', $line, $matches))
        $snapshots[count($snapshots)-1]['mem_stacks_B'] = $matches[1];
}

fclose($file);

/////////////////////////////////

//echo('<script>');
?>

    <script>
        window.chartColors = {
            red: 'rgb(255, 99, 132)',
            orange: 'rgb(255, 159, 64)',
            yellow: 'rgb(255, 205, 86)',
            green: 'rgb(75, 192, 192)',
            blue: 'rgb(54, 162, 235)',
            purple: 'rgb(153, 102, 255)',
            grey: 'rgb(201, 203, 207)'
        };

        var config = {
            type: 'line',
            data: {
                labels: [
<?php
                foreach($snapshots as $value)
                    echo($value["time"].",");
?>
                ],
                datasets: [
                {
                    label: 'mem_heap_B',
                    borderColor: window.chartColors.red,
                    backgroundColor: window.chartColors.red,
                    data: [
<?php
                    foreach($snapshots as $value)
                        echo($value["mem_heap_B"].",");
?>
                    ],
                },
                {
                    label: 'mem_heap_extra_B',
                    borderColor: window.chartColors.green,
                    backgroundColor: window.chartColors.green,
                    data: [
<?php
                    foreach($snapshots as $value)
                        echo($value["mem_heap_extra_B"].",");
?>
                    ],
                },
                {
                    label: 'mem_stacks_B',
                    borderColor: window.chartColors.blue,
                    backgroundColor: window.chartColors.blue,
                    data: [
<?php
                    foreach($snapshots as $value)
                        echo($value["mem_stacks_B"].",");
?>
                    ],
                },
            ]},
            options: {
                responsive: true,
                title: {
                    display: true,
                    text: 'Massif output graph'
                },
                tooltips: {
                    mode: 'index',
                },
                hover: {
                    mode: 'index'
                },
                scales: {
                    xAxes: [{
                        scaleLabel: {
                            display: true,
                            labelString: 'Time sample'
                        }
                    }],
                    yAxes: [{
                        stacked: true,
                        scaleLabel: {
                            display: true,
                            labelString: 'Size (bytes)'
                        }
                    }]
                }
            }
        };

        window.onload = function() {
            var ctx = document.getElementById('canvas').getContext('2d');
            window.myLine = new Chart(ctx, config);
        };

    </script>

<?php

// print_r($snapshots);

?>

</body>

</html>
