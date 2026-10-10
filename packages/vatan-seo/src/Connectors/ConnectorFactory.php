<?php

namespace Vatan\Seo\Connectors;

use Vatan\Seo\Models\Site;

class ConnectorFactory
{
    public const PLATFORMS = [
        'laravel_local' => 'لاراول (همین پروژه)',
        'wordpress' => 'وردپرس',
    ];

    public static function for(Site $site): SiteConnector
    {
        return match ($site->platform) {
            'wordpress' => new WordPressConnector($site),
            default => new LaravelLocalConnector($site),
        };
    }
}
