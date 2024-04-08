<?php
use Illuminate\Support\Facades\Route;

Route::group(['middleware' => ['statamic.cp.authenticated'], 'namespace' => 'AltDesign\AltCookiesAddon\Http\Controllers'], function() {
    // Settings
    Route::get('/alt-design/alt-cookies/', 'AltCookiesController@index')->name('alt-cookies-addon.index');
    Route::post('/alt-design/alt-cookies/save', 'AltCookiesController@save')->name('alt-cookies-addon.save');
});
