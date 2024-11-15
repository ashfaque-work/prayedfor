<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PrayerRequest extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'location_id',
        'contact_id',
        'prayedfor_msg',
        'flagged_req',
        'last_time',
        'last_date',
        'prayer_sent_today',
        'prayed_count_today',
        'prayed_count_all',
        'status',
    ];

    public function location(): BelongsTo 
    {
        return $this->belongsTo(Location::class);
    }
    public function contact(): BelongsTo 
    {
        return $this->belongsTo(Contact::class);
    }
}
