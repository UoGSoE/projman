<?php

return [
    'https' => env('HTTPS_PROXY'),
    'http' => env('HTTP_PROXY'),
    'no' => array_filter(array_map('trim', explode(',', env('NO_PROXY', '')))),
];
