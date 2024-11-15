<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Contact extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'location_id',
        'contactId',
        'name',
        'email',
        'phone',
        'tags',
        'flagged',
        'customFields',
    ];
    
    public function location(): BelongsTo 
    {
        return $this->belongsTo(Location::class);
    }
    
    public function prayerRequest(): HasMany 
    {
        return $this->hasMany(PrayerRequest::class);
    }
    
    public function prayerWarrior(): HasOne 
    {
        return $this->hasOne(PrayerWarrior::class);
    }
}
