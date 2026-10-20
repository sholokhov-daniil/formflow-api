<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::prefix('doc')->name('doc.')->group(function () {
    Route::view('/api', 'doc.api')->name('api');

    Route::get('/api/openapi.yaml', function () {
        return response()->file(base_path('docs/api/openapi.yaml'), [
            'Content-Type' => 'application/yaml; charset=UTF-8',
        ]);
    })->name('api.specification');
});
