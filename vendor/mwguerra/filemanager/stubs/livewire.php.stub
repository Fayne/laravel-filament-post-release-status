<?php

/*
|--------------------------------------------------------------------------
| Livewire Configuration for FileManager
|--------------------------------------------------------------------------
|
| This configuration is optimized for the FileManager package.
| Publish this to config/livewire.php to enable large file uploads.
|
*/

return [

    'class_namespace' => 'App\\Livewire',
    'view_path' => resource_path('views/livewire'),
    'layout' => 'components.layouts.app',

    'legacy_model_binding' => false,

    'inject_assets' => true,

    'inject_morph_markers' => true,

    'navigate' => [
        'show_progress_bar' => true,
        'progress_bar_color' => '#2299dd',
    ],

    'temporary_file_upload' => [
        'disk' => null,
        'rules' => ['required', 'file', 'max:102400'], // 100MB max
        'directory' => null,
        'middleware' => null,
        'preview_mimes' => [
            'png', 'gif', 'bmp', 'svg', 'wav', 'mp4',
            'mov', 'avi', 'wmv', 'mp3', 'm4a',
            'jpg', 'jpeg', 'mpga', 'webp', 'wma',
            'pdf', 'doc', 'docx', 'xls', 'xlsx', 'zip',
        ],
        'max_upload_time' => 15, // 15 minutes for large files
        'cleanup' => true,
    ],

    'render_on_redirect' => false,

    'pagination_theme' => 'tailwind',

];
