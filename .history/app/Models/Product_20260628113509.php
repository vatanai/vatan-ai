<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = ['name', 'description', 'prompt', 'image'];

    public function generations()
    {
        return $table->hasMany(Generation::class);
    }
}