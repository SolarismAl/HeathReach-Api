<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    use HasFactory;

    protected $fillable = [
        'service_id',
        'health_center_id',
        'service_name',
        'description',
        'duration_minutes',
        'price',
        'is_active',
        'schedule',
    ];

    protected $casts = [
        'schedule' => 'array',
        'is_active' => 'boolean',
        'price' => 'decimal:2',
    ];

    public function healthCenter()
    {
        return $this->belongsTo(HealthCenter::class, 'health_center_id', 'health_center_id');
    }

    public function appointments()
    {
        return $this->hasMany(Appointment::class, 'service_id', 'service_id');
    }
}
