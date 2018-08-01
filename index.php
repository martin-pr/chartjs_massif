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
    <canvas id="canvas_overall"></canvas>
</div>

<div style="width:75%;">
    <canvas id="canvas_detailed"></canvas>
</div>

<?php

$file = fopen('massif.out.5055', 'r');
$stacktrace_keys = array();
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

    else if(preg_match('/^heap_tree=([a-z]+)$/', $line, $matches))
        $snapshots[count($snapshots)-1]['heap_tree'] = $matches[1];

    // else if(preg_match('/^[ ]*n([0-9]+): ([0-9]+) (.*)$/', $line, $matches))
    //     print_r($matches);

    // only first-level stacktraces for the graph
    else if(preg_match('/^ n([0-9]+): ([0-9]+) (.*)$/', $line, $matches)) {
        if(strpos($matches[3], 'all below massif') === false) {
            $stacktrace_keys[$matches[3]] = true;
            $snapshots[count($snapshots)-1]['stacktrace'][$matches[3]] = $matches[2];
        }
        else {
            $stacktrace_keys['below threshold'] = true;
            $snapshots[count($snapshots)-1]['stacktrace']['below threshold'] = $matches[2];
        }
    }

}

fclose($file);

/////////////////////////////////

//echo('<script>');

    function colour($val) {
        $elems = array(
            array(1,0,0),
            array(1,1,0),
            array(0,1,0),
            array(0,1,1),
            array(0,0,1),
            array(0,0,0),
        );

        $v = $val * floatval((count($elems)-1));

        $weight = $v - floor($v);
        // echo $weight;

        // echo $elems[intval(floor($v))][0] * (1.0 - $weight);

        $elem = array();
        $elem[0] = $elems[intval(floor($v))][0] * (1 - $weight) + $elems[intval(ceil($v))][0] * ($weight);
        $elem[1] = $elems[intval(floor($v))][1] * (1 - $weight) + $elems[intval(ceil($v))][1] * ($weight);
        $elem[2] = $elems[intval(floor($v))][2] * (1 - $weight) + $elems[intval(ceil($v))][2] * ($weight);

        return 'rgb('.($elem[0]*255).",".($elem[1]*255).",".($elem[2]*255).")";
        // return 'rgb(0,0,0)';
    }

?>

    <script>
        var config_detailed = {
            type: 'line',
            data: {
                labels: [
<?php
                foreach($snapshots as $value)
                    if(array_key_exists("stacktrace", $value))
                        echo($value["time"].",");
?>
                ],
                datasets: [
<?php
                $index = 0;
                foreach($stacktrace_keys as $key => $value) {
                    $color = $index / (count($stacktrace_keys)-1);
                    $index++;

                    echo("{\n");
                    echo("label: '".addslashes($key)."',\n");
                    echo("borderColor: '".colour($color)."',\n");
                    echo("borderWidth: 1,\n");
                    echo("backgroundColor: '".colour($color)."',\n");
                    echo("data: [");

                    foreach($snapshots as $snapshot)
                        if(array_key_exists('stacktrace', $snapshot)) {
                            if(array_key_exists($key, $snapshot['stacktrace']))
                                echo($snapshot['stacktrace'][$key].',');
                            else
                                echo("0,");
                        }

                    echo("]");
                    echo("},");
                }
?>
                ]
            },
            options: {
                responsive: true,
                title: {
                    display: true,
                    text: 'Massif detailed output graph'
                },
                tooltips: {
                    mode: 'index',
                },
                hover: {
                    mode: 'index'
                },
                legend: {
                   display: false
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

    </script>

<!-- --------------------------------------------------------------------------- -->

<?php

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

        var config_overall = {
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
                    borderColor: window.chartColors.blue,
                    backgroundColor: window.chartColors.blue,
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
            var ctx = document.getElementById('canvas_overall').getContext('2d');
            window.myLine = new Chart(ctx, config_overall);

            var ctx = document.getElementById('canvas_detailed').getContext('2d');
            window.myLineDetailed = new Chart(ctx, config_detailed);

            document.getElementById("canvas_detailed").onclick = function(evt){
                var activePoints = window.myLineDetailed.getElementsAtEvent(evt);
                // use _datasetIndex and _index from each element of the activePoints array
                alert(activePoints);
            };
        };


    </script>

<?php

// print_r($snapshots);

?>

</body>

</html>
