<?php

return [
    'environment_label' => env('HEAVYPACK_ENVIRONMENT_LABEL', 'Katalog lokal untuk pengujian'),
    'source_database' => env('HEAVYPACK_SOURCE_DATABASE', 'heavypack'),
    'content_mode' => env('HEAVYPACK_CONTENT_MODE', 'testing'),
    'website_root' => env('HEAVYPACK_WEBSITE_ROOT', 'C:/xampp/htdocs/public_html'),
    'website_url' => env('HEAVYPACK_WEBSITE_URL', 'http://localhost/public_html'),
    'preview_root' => base_path('.runtime/preview/public_html'),
    'preview_url' => env('HEAVYPACK_PREVIEW_URL', 'http://127.0.0.1:8002'),
];
