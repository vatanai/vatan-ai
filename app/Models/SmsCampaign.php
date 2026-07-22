<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class SmsCampaign extends Model {
 protected $fillable=['name','audience_key','template_id','body','status','scheduled_at','recipient_count','sent_count','failed_count','created_by'];
 protected $casts=['scheduled_at'=>'datetime'];
 public function template(){return $this->belongsTo(SmsTemplate::class);}
}
