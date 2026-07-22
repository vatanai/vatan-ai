<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class SmsProvider extends Model {
 protected $fillable=['name','driver','api_key','sender','base_url','is_active','is_default','settings','last_error','last_checked_at'];
 protected $casts=['api_key'=>'encrypted','settings'=>'array','is_active'=>'boolean','is_default'=>'boolean','last_checked_at'=>'datetime'];
}
