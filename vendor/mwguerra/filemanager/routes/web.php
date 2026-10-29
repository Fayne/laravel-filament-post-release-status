<?php

use Illuminate\Support\Facades\Route;
use MWGuerra\FileManager\Http\Controllers\FileStreamController;

/*
|--------------------------------------------------------------------------
| FileManager Web Routes
|--------------------------------------------------------------------------
|
| These routes handle file streaming for disks that don't have direct
| web access. All routes use signed URLs for security.
|
*/

$routePrefix = config('filemanager.streaming.route_prefix', 'filemanager');
$middleware = config('filemanager.streaming.middleware', ['web']);

Route::middleware($middleware)
    ->prefix($routePrefix)
    ->name('filemanager.')
    ->group(function () {
        // Stream file for inline viewing (preview)
        Route::get('/stream', [FileStreamController::class, 'stream'])
            ->name('stream');

        // Stream file for download (attachment)
        Route::get('/download', [FileStreamController::class, 'download'])
            ->name('download');
    });
