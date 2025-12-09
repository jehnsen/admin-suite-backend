<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'message' => 'AdminSuite API',
        'version' => '1.0.0',
        'documentation' => url('/docs'),
        'health' => url('/api/health'),
    ]);
});
