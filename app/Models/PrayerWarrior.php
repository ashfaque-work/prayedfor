<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PrayerWarrior extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'location_id',
        'contact_id',
        'frequency',
        'count',
        'status',
        'last_time',
        'last_date',
        'prayer_sent',
        'prayed_for',
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
