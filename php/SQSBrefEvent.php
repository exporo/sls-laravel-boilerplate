<?php
define('LARAVEL_START', microtime(true));

require __DIR__ . '/../application/vendor/autoload.php';
$app = require_once __DIR__ . '/../application/bootstrap/app.php';

$kernel = $app->make(\App\Console\Kernel::class);
$kernel->bootstrap();
$container = resolve(\Psr\Container\ContainerInterface::class);

lambda(function (array $event) use ($container) {

    $statuses = [];

    foreach ($event['Records'] ?? [] as $record) {
        $job = new \Illuminate\Queue\Jobs\SyncJob($container, $record['body'], 'sync', 'sync');
        $job->fire();
        $statuses[] = 1;
    }

    echo PHP_EOL;
    echo "------queue-event";
    echo PHP_EOL;
    echo count($statuses) . " messages were executed.";
    echo PHP_EOL;

    return [
        'statusCode' => 200,
        'headers' => [
            'Content-Type' => 'application/json',
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Credentials' => true,
        ],
        'body' => ['message' => 'All good in the hood :)'],
        'status' => $statuses,
    ];
});