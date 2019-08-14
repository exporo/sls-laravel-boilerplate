<?php

namespace App\Console\Commands;

use GuzzleHttp\Client;
use Illuminate\Console\Command;

class KeepWarm extends Command
{

    protected $signature = 'keep-warm';
    protected $description = 'Keep the functions warm';
    protected $http;

    public function __construct(Client $http)
    {
        $this->http = $http;
        parent::__construct();
    }

    public function handle()
    {
        $this->http->get(config('app.url'))->getStatusCode();
    }
}
