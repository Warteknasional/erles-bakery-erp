<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'status' => 'online',
        'service' => 'Erles Bakery ERP API Server',
        'message' => 'Backend API aktif dan terhubung ke database PostgreSQL.',
        'version' => '1.0.0',
        'portal' => [
            'storefront' => 'https://erlesbakery.netlify.app',
            'admin_erp' => 'https://erlesbakery-admin.netlify.app',
        ],
        'sample_endpoints' => [
            'products' => '/api/products',
            'categories' => '/api/categories',
            'login' => '/api/login',
            'health' => '/up',
        ],
    ], 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
});
