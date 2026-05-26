<?php

$allowedEnvironments = array_values(array_filter(array_map(
    static fn (string $environment): string => trim($environment),
    explode(',', (string) env('DEMO_ALLOWED_ENVIRONMENTS', 'local,development,staging,testing')),
)));

return [
    'enabled' => (bool) env('DEMO_ENABLED', true),
    'read_only' => (bool) env('DEMO_READ_ONLY', false),
    'password' => (string) env('DEMO_PASSWORD', 'DemoPass!123'),
    'allowed_environments' => $allowedEnvironments,
];
