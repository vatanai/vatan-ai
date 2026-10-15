<?php

namespace Vatan\Content\Http\Controllers;

use Illuminate\Routing\Controller as Base;

abstract class Controller extends Base
{
    protected function ws(): int
    {
        return (int) config('content-factory.workspace_id', 1);
    }

    protected function adminName(): string
    {
        $u = auth(config('content-factory.host.admin_guard', 'admin'))->user();

        return (string) ($u->name ?? $u->email ?? $u->mobile ?? 'admin');
    }
}
