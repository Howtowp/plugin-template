<?php

declare(strict_types=1);

namespace Howtowp\Bluwp\UI\Fields;

defined('ABSPATH') || exit;

class UrlField extends Field
{
    protected string $type = 'url';

    public function validate($value): ?string
    {
        $error = parent::validate($value);
        if (null !== $error || null === $value || '' === $value) {
            return $error;
        }
        $url = (string) $value;
        $scheme = strtolower((string) wp_parse_url($url, PHP_URL_SCHEME));
        if (! in_array($scheme, [ 'http', 'https' ], true) || false === filter_var($url, FILTER_VALIDATE_URL)) {
            return __('Enter a complete HTTP or HTTPS URL.', BLUWP);
        }
        return null;
    }

    public function sanitize($value)
    {
        return null === $value ? '' : esc_url_raw((string) $value, [ 'http', 'https' ]);
    }
}
