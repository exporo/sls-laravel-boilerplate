<?php

namespace App\Console\Commands;

use App\CountDb;
use App\Listeners\CounterCache;
use App\Listeners\CounterS3;
use Illuminate\Console\Command;

class ClearCounter extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'clear:counters';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        \Storage::delete(CounterS3::FILENAME);
        \Cache::forget(CounterCache::CACHEKEY);
        CountDb::truncate();
    }
}
