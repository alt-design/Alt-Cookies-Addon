<?php
use Illuminate\Support\Facades\Route;

Route::group(['middleware' => ['statamic.cp.authenticated'], 'namespace' => 'AltDesign\AltCookiesAddon\Http\Controllers'], function() {
    
    // Settings
    Route::group(['middleware' => ['can:view alt-cookies-addon']], function () {
        Route::get('/alt-design/alt-cookies/', 'AltCookiesController@index')->name('alt-cookies-addon.index');
        Route::post('/alt-design/alt-cookies/save', 'AltCookiesController@save')->name('alt-cookies-addon.save');
    });
    
    // Statamic V6 redirect the Addon settings route
    Route::get('/addons/alt-cookies/settings', fn () => redirect()->to(cp_route('alt-cookies-addon.index')));
});
