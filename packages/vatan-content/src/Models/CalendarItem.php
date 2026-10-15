<?php

namespace Vatan\Content\Models;

use Illuminate\Database\Eloquent\Model;

class CalendarItem extends Model
{
    protected $table = 'cf_calendar_items';
    protected $guarded = ['id'];
    protected $casts = ['stories' => 'array', 'result' => 'array', 'publish_date' => 'date', 'scenes' => 'array', 'story_specs' => 'array', 'keywords' => 'array', 'ai_plan' => 'array', 'assets' => 'array'];

    public function calendar() { return $this->belongsTo(Calendar::class, 'calendar_id'); }
    public function production() { return $this->hasOne(Production::class, 'calendar_item_id'); }
    public function styleModel() { return $this->belongsTo(Style::class, 'style_id'); }
    public function musicTrack() { return $this->belongsTo(MusicTrack::class, 'music_id'); }
}
