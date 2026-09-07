<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FinanceCostCenter extends Model
{
    protected $fillable = ['name', 'code', 'description', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function transactions()
    {
        return $this->hasMany(FinanceTransaction::class, 'cost_center_id');
    }
}
