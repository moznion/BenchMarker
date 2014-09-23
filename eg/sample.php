<?php

//require '../lib/Benchmarker.php';
require_once '../vendor/autoload.php';

$benchmarker = new Benchmarker();
$bench = $benchmarker->timeit(100, function () {});

var_dump($bench);

