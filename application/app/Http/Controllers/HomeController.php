<?php

namespace App\Http\Controllers;

use App\CountDb;
use App\Events\Count;
use App\Listeners\CounterCache;
use App\Listeners\CounterS3;

class HomeController extends Controller
{
    public function index()
    {
        $s3 = \Storage::exists(CounterS3::FILENAME) ? \Storage::get(CounterS3::FILENAME) : 0;
        $dynamoDb = \Cache::get(CounterCache::CACHEKEY) ?? 0;
        $auroraServerless = CountDb::first() ? CountDb::first()->counter : 0;

        \event(new Count());

        return view('welcome', [
            'counter' => [
                'S3 Storage' => $s3,
                'DynamoDB Cache' => $dynamoDb,
                'Aurora Serverless MySql 5.6 DB' => $auroraServerless,
            ]
        ]);
    }
}
