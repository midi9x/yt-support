<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| This file is where you may define all of the routes that are handled
| by your application. Just tell Laravel the URIs it should respond
| to using a Closure or controller method. Build something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});
Route::resource('panel', 'PllController@test');

Route::resource('yt_report', 'YTReportingController');
Route::resource('pll', 'PllController');
Route::post('pll/search', 'PllController@search');
Route::get('view', 'PllController@view');
Route::post('pll/createPll', 'PllController@createPll');
