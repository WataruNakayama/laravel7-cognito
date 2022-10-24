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

Auth::routes();

Route::get('/home', 'HomeController@index')->name('home');
Route::get('/change_password', 'Auth\ChangePasswordController@show')->name('showChangePassword');
Route::post('/change_password', 'Auth\ChangePasswordController@update')->name('updateChangePassword');
Route::get('/change_email', 'Auth\ChangeEmailController@show')->name('showChangeEmail');
Route::post('/change_email', 'Auth\ChangeEmailController@update')->name('updateChangeEmail');
Route::get('/password/register/{token}', 'Auth\RegisterController@showPasswordForm')->name('showPasswordForm');
Route::post('/password/register', 'Auth\RegisterController@updatePassword')->name('updatePassword');
Route::get('/register_complete', 'Auth\RegisterController@complete')->name('registerComplete');
