<?php

use Illuminate\Support\Facades\Route;

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

Route::get('/', function () {
    return view('welcome');
});

/*
|--------------------------------------------------------------------------
| API Routes for APK APPs. This will be deployed in subdomain.
|--------------------------------------------------------------------------
|
*/

Route::get("/api/apk/{id}", "ApkController@index")->name("getApk");
Route::get("/api/checkApk/{id}", "ApkController@checkDuplicate")->name("checkApk");

Route::get("/dusk", "DuskController@duskTest");
