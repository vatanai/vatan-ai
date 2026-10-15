<?php

namespace Vatan\Content\Models;

use Illuminate\Database\Eloquent\Model;

class PromptVersion extends Model
{
    protected $table = 'cf_prompt_versions';
    protected $guarded = ['id'];
    protected $casts = ['depends_on' => 'array'];

}
