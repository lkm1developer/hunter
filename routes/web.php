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

Route::get('/', function () {
    return view('welcome');
});

Auth::routes();

Route::get('/home', 'HomeController@index')->name('home');
Route::get('/stats', 'StaticController@CryptoHunterStats');
Route::get('/stats/{id}', 'StaticController@CryptoHunterStats');
Route::fallback(function(){
    return response()->json(['message' => 'Not Found.'], 404);
})->name('api.fallback.404');