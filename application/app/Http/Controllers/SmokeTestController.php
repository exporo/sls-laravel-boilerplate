<?php

namespace App\Http\Controllers;

use Exception;
use App\CountDb;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Storage;

class SmokeTestController extends Controller
{
    protected $guzzle;

    protected $tests = [
        'passed' => [],
        'failed' => []
    ];

    public function __construct(Client $client)
    {
        $this->guzzle = $client;
    }

    public function index()
    {
        foreach (get_class_methods($this) as $method) {
            if (substr($method, 0, 4) === 'test') {
                $this->{$method}();
            }
        }

        return response()->json($this->tests, count($this->tests['failed']) > 0 ? 500 : 200);
    }

    protected function testIfDatabaseIsAccessible()
    {
        try {
            CountDb::get()->count();
        } catch (Exception $e) {
            return $this->tests['failed'][] = __FUNCTION__;
        }

        $this->tests['passed'][] = __FUNCTION__;
    }

    protected function testIfStorageIsNotPublic()
    {
        $tmpFileName = 'tmp.txt';
        Storage::put($tmpFileName, 'test');
        $fileUrl = Storage::url($tmpFileName);

        try {
            $response = $this->guzzle->get($fileUrl, ['http_errors' => false]);
        } catch (Exception $e) {
        } finally {
            if (isset($response) && $response->getStatusCode() !== 403) {
                return $this->tests['failed'][] = __FUNCTION__;
            }
        }

        $this->tests['passed'][] = __FUNCTION__;
    }

    protected function testIfAssetsWereAccessible()
    {
        try {
            $response = $this->guzzle->get(asset('exporo-tech.png'), ['http_errors' => false]);
        } catch (Exception $e) {
        } finally {
            if (!isset($response) || $response->getStatusCode() !== 200) {
                return $this->tests['failed'][] = __FUNCTION__;
            }
        }

        $this->tests['passed'][] = __FUNCTION__;
    }

    protected function testIfApplicationCanAccessTheInternet()
    {
        try {
            $response = $this->guzzle->get('https://google.de', ['http_errors' => false]);
        } catch (Exception $e) {
        } finally {
            if (!isset($response) || $response->getStatusCode() !== 200) {
                return $this->tests['failed'][] = __FUNCTION__;
            }
        }

        $this->tests['passed'][] = __FUNCTION__;
    }
}
