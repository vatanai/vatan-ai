<?php

namespace App\Models\SmartInstagram;

use App\Models\Admin;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContactNote extends Model
{
    protected $table = 'instagram_contact_notes';

    protected $fillable = ['workspace_id', 'contact_id', 'admin_id', 'body'];

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class);
    }
}
