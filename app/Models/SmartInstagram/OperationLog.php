<?php

namespace App\Models\SmartInstagram;

use App\Models\Admin;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OperationLog extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'instagram_operation_logs';

    protected $fillable = ['workspace_id', 'action', 'level', 'subject_type', 'subject_id', 'admin_id', 'message', 'context'];

    protected function casts(): array
    {
        return ['context' => 'array'];
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class);
    }
}
