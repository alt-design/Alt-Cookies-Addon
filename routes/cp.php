<?php
use Illuminate\Support\Facades\Route;

Route::group(['middleware' => ['statamic.cp.authenticated', 'can:view alt-cookies-addon'], 'namespace' => 'AltDesign\AltCookiesAddon\Http\Controllers'], function() {
    // Settings
    Route::get('/alt-design/alt-cookies/', 'AltCookiesController@index')->name('alt-cookies-addon.index');
    Route::post('/alt-design/alt-cookies/save', 'AltCookiesController@save')->name('alt-cookies-addon.save');

    // Cookie scan
    Route::get('/alt-design/alt-cookies/scan', 'CookieScanController@redirectToTab')->name('alt-cookies-addon.scan.index');
    Route::post('/alt-design/alt-cookies/scan', 'CookieScanController@scan')->name('alt-cookies-addon.scan.run');
    Route::post('/alt-design/alt-cookies/scan/clear', 'CookieScanController@clear')->name('alt-cookies-addon.scan.clear');
});
