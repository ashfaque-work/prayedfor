<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class Location extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'locationId',
        'access_token',
        'refresh_token',
        'companyId',
    ];
    
    public function churchSettings(): HasOne 
    {
        return $this->hasOne(churchSetting::class);
    }
    
    public function contact(): HasMany
    {
        return $this->hasMany(Contact::class);
    }
    
    public function responseTemplate(): HasOne 
    {
        return $this->hasOne(ResponseTemplate::class);
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
