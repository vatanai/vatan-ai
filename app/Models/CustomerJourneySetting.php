<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerJourneySetting extends Model
{
    protected $primaryKey = 'key';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['key', 'label', 'value', 'updated_by'];
}
