<?php

header('Content-Type: application/json');

echo json_encode([
    'status' => 'PHP IS WORKING',
    'php_version' => PHP_VERSION,
    'app_key_exists' => !empty(getenv('APP_KEY')),
    'supabase_url_exists' => !empty(getenv('SUPABASE_URL')),
    'supabase_service_exists' => !empty(getenv('SUPABASE_SERVICE_ROLE_KEY')),
]);