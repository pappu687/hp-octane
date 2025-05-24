<?php

use App\Http\Controllers\RemoteDataController;
use App\Http\Controllers\TestController;
use App\Http\Controllers\WrkController;
use Illuminate\Support\Facades\Route;
use Laravel\Octane\Facades\Octane;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/boot-test', [ TestController::class, 'bootTest' ]);

Route::get('/cache-hit', [ TestController::class, 'cacheHit' ]);

Route::get('/boot-time', function () {
    return 'Booted at: ' . Octane::cache('boot_time');
});

Route::get('/concurrent', [ TestController::class, 'concurrent' ]);
Route::get('/hit-counter', [ TestController::class, 'hitCounter' ]);

Route::get('/remote', [ RemoteDataController::class, 'index' ])->name('remote');
Route::get('/remote-concurrent/{no_cache?}', [ RemoteDataController::class, 'fetch' ])->name('remote.concurrent');

Route::get('/wrk', [ WrkController::class, 'index' ])->name('wrk.index');
Route::post('/wrk/parse', [ WrkController::class, 'parse' ])->name('wrk.parse');
