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

        html {
            font-family: sans-serif;
            font-size: 9px;
        }

        ul.tree {
            margin-bottom: 1em;
        }

        ul.tree li {
            list-style-type: none;
            position: relative;
            white-space: pre;
        }

        ul.tree li ul {
            display: none;
        }

        ul.tree li.open > ul {
            display: block;
        }

        ul.tree li a {
            color: black;
            text-decoration: none;
        }

        ul.tree li a:hover {
            background-color: #ccc;
        }

        ul.tree li a:before {
            height: 1em;
            padding:0 .1em;
            font-size: .8em;
            display: block;
            position: absolute;
            left: -1.3em;
            top: .2em;
        }

        ul.tree li > a:not(:last-child):before {
            content: '+';
        }

        ul.tree li.open > a:not(:last-child):before {
            content: '-';
        }

        ul {
            list-style-type: none;
            margin: 0;
            padding: 0 0 0 1em;
        }

    </style>
</head>

<body>

<div style="float: right; width: 25%; ">

<?php

function human_memsize($bytes, $decimals = 2) {
    $size = array('B','kB','MB','GB','TB','PB','EB','ZB','YB');
    $factor = floor((strlen($bytes) - 1) / 3);
    return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . @$size[$factor];
}


$file = fopen($_FILES['massif_log']['tmp_name'], 'r');
$stacktrace_keys = array();
$snapshots = array();

$level = -1;
$detailed_counter = 0;

while (($line = fgets($file)) !== false) {
    if(preg_match('/^snapshot=([0-9]+)$/', $line, $matches)) {
        array_push($snapshots, array());
        while($level >= 0) {
            echo('</li></ul>');
            $level = $level - 1;
        }
    }

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

    if(preg_match('/^([ ]*)n([0-9]+): ([0-9]+) (0x[0-9A-Z]+: )?(.*)$/', $line, $matches)) {
        $newlevel = strlen($matches[1]);
        if($newlevel > $level) {
            assert($newlevel - $level == 1);

            if($newlevel == 0) {
                echo('<ul class="tree" id="snapshot_'.$detailed_counter.'">');
                $detailed_counter++;
            }
            else
                echo('<ul>');

            $level++;

        }
        else if($newlevel < $level) {
            while($level > $newlevel) {
                echo('</li></ul>');
                $level--;
            }
        }
        else {
            assert($level == $newlevel);

            echo('</li>');
        }

        if($level == 0)
            echo('<li class="open">');
        else
            echo("<li>");

        echo '<a href="#">'.human_memsize($matches[3]).' '.$matches[5].'</a>';

    }

}

fclose($file);

?>
</div>

<div style="width:75%;">
    <canvas id="canvas_detailed"></canvas>
</div>

<div style="width:75%;">
    <canvas id="canvas_overall"></canvas>
</div>

<?php

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
                    mode: 'none',
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
                    mode: 'none',
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
                // alert('snapshot_' + activePoints[0]._index.toString());

                var sts = document.querySelectorAll('ul.tree');
                for(var i = 0; i < sts.length; i++)
                    sts[i].style.display = 'none';

                var current = document.querySelector('#snapshot_' + activePoints[0]._index.toString());
                current.style.display = 'initial';
            };


            var tree = document.querySelectorAll('ul.tree a:not(:last-child)');
            for(var i = 0; i < tree.length; i++){
                tree[i].addEventListener('click', function(e) {
                    var parent = e.target.parentElement;
                    var classList = parent.classList;
                    if(classList.contains("open")) {
                        classList.remove('open');
                        var opensubs = parent.querySelectorAll(':scope .open');
                        for(var i = 0; i < opensubs.length; i++){
                            opensubs[i].classList.remove('open');
                        }
                    } else {
                        classList.add('open');
                    }
                });
            }
        };


    </script>

<?php

// print_r($snapshots);

?>

</body>

</html>
