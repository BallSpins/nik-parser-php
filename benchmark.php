<?php
// benchmark.php
require __DIR__ . '/vendor/autoload.php';
use Ballspins\NikParser\NikParser;

$iterations = 1000000;
echo "Running benchmark for $iterations iterations...\n";

$start = microtime(true);

for($i = 0; $i < $iterations; $i++) { 
    $parser = new NikParser('3578201503990001');
    $parser->kecamatan();
    $parser->birthDate();
}

$end = microtime(true);
$peakMem = memory_get_peak_usage(true) / 1024 / 1024;

echo "------------------------------------------\n";
echo "PHP Execution Time: " . round($end - $start, 4) . " seconds\n";
echo "Peak Memory Usage: " . round($peakMem, 4) . " MB\n";
echo "------------------------------------------\n";
