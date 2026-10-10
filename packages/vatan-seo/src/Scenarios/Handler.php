<?php

namespace Vatan\Seo\Scenarios;

use Vatan\Seo\Models\Scenario;
use Vatan\Seo\Models\Site;

interface Handler
{
    /** @return array{0:string,1:string,2?:array} [status(success|warning|skipped|failed), summary, output] */
    public function handle(Site $site, Scenario $scenario): array;
}
