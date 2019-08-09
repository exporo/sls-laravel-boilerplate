<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/


use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/loaderio-5b7b1bc1bd688c7bb758c923fed60162', function (Request $request) {
    return 'loaderio-5b7b1bc1bd688c7bb758c923fed60162';
});


Route::get('/', 'HomeController@index');

Route::get('/migrate', function (Request $request) {

    if (!\Cache::has('test')) {
        \Cache::set('test', 1);
    }

    \Cache::increment('test');
    \Artisan::call('migrate', array('--path' => 'app/migrations', '--force' => true));
});
