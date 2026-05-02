<?php

namespace App\Support;

use Illuminate\Support\Str;

trait ResolvesLogoDataUri
{
    protected function resolveLogoDataUri(?string $path): ?string
    {
        if (blank($path)) {
            return null;
        }

        if (Str::startsWith($path, ['data:', 'http://', 'https://'])) {
            return $path;
        }

        $normalizedPath = ltrim($path, '/');
        $candidates = [
            storage_path('app/' . $normalizedPath),
            storage_path('app/public/' . Str::after($normalizedPath, 'public/')),
            public_path($normalizedPath),
        ];

        foreach ($candidates as $candidate) {
            if (!is_file($candidate)) {
                continue;
            }

            $mime = mime_content_type($candidate) ?: 'image/png';
            $content = file_get_contents($candidate);

            if ($content === false) {
                continue;
            }

            return 'data:' . $mime . ';base64,' . base64_encode($content);
        }

        return null;
    }
}
