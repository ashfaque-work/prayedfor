<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class churchSetting extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'location_id',
        'prayer_w_qnty',
        'prayer_req_qnty',
        'prayer_req_alltime',
        'time_gap_from',
        'time_gap_to',
        'time_interval',
        'time_zone'
    ];

    public function location(): BelongsTo 
    {
        return $this->belongsTo(Location::class);
    }
}
