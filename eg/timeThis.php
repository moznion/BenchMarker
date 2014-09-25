<?php

require_once __DIR__ . '/../vendor/autoload.php';

$benchmarker = new \Moznion\BenchMarker();

// run 100 times
$bench = $benchmarker->timeThis(100,
    function () {
        // do something
    }, 'awesome title'
);

// run at least for 1 seconds
$bench = $benchmarker->timeThis(-1,
    function () {
        // do something
    }, 'awesome title'
);

