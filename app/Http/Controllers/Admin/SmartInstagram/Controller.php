<?php

namespace App\Http\Controllers\Admin\SmartInstagram;

use App\Http\Controllers\Controller as BaseController;
use App\Models\Admin;
use App\Services\SmartInstagram\WorkspaceContext;

abstract class Controller extends BaseController
{
    public function __construct(protected readonly WorkspaceContext $context)
    {
    }

    protected function admin(): ?Admin
    {
        return auth('admin')->user();
    }

    protected function authorizeAbility(string $ability): void
    {
        $this->context->authorize($this->admin(), $ability);
    }

    protected function ws(): int
    {
        return $this->context->id();
    }
}
