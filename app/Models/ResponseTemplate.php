<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResponseTemplate extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'location_id',
        'template'
    ];

    public function location(): BelongsTo 
    {
        return $this->belongsTo(Location::class);
    }
}
