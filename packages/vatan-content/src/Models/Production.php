<?php

namespace Vatan\Content\Models;

use Illuminate\Database\Eloquent\Model;

class Production extends Model
{
    protected $table = 'cf_productions';
    protected $guarded = ['id'];
    protected $casts = ['gene' => 'array', 'published_at' => 'datetime', 'keywords' => 'array', 'scores' => 'array', 'scored_at' => 'datetime', 'auto_reply' => 'boolean'];

    public function item() { return $this->belongsTo(CalendarItem::class, 'calendar_item_id'); }
    public function versions() { return $this->hasMany(ProductionVersion::class, 'production_id')->orderByDesc('version'); }
    public function latestVersion() { return $this->hasOne(ProductionVersion::class, 'production_id')->orderByDesc('version'); }
    public function edits() { return $this->hasMany(SegmentEdit::class, 'production_id')->latest(); }
    public function music() { return $this->belongsTo(MusicTrack::class, 'music_id'); }
    public function snapshots() { return $this->hasMany(MetricSnapshot::class, 'production_id'); }
    public function jobs() { return $this->hasMany(Job::class, 'production_id')->latest('id'); }
}
