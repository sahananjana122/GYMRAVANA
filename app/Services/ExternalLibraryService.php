<?php

namespace App\Services;

class ExternalLibraryService
{
    public function details(): array
    {
        $url = trim((string) config('gymravana.library.url'));
        $scheme = $url ? strtolower((string) parse_url($url, PHP_URL_SCHEME)) : null;
        $isValid = $url
            && filter_var($url, FILTER_VALIDATE_URL)
            && in_array($scheme, ['http', 'https'], true);

        return [
            'url' => $isValid ? $url : null,
            'label' => (string) config('gymravana.library.label', 'GymRAVANA books and movies'),
        ];
    }
}
