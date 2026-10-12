<?php

use Euginepj\ApiLogger\Http\Controllers\ApiLogViewerController;
use Illuminate\Support\Facades\Route;

$prefix = config('api-logger.dashboard.prefix', 'admin/api-logs');
$middleware = config('api-logger.dashboard.middleware', ['web', 'auth', 'role:Admin|Staff']);

Route::middleware($middleware)->prefix($prefix)->name('admin.api-logs.')->group(function () {
    Route::get('/', [ApiLogViewerController::class, 'index'])->name('index');
    Route::post('/clear', [ApiLogViewerController::class, 'clear'])->name('clear');
    Route::get('/{id}', [ApiLogViewerController::class, 'show'])->name('show');
});
