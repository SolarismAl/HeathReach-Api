<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HealthCenter extends Model
{
    use HasFactory;

    protected $fillable = [
        'health_center_id',
        'name',
        'location',
        'contact_number',
    ];

    public function services()
    {
        return $this->hasMany(Service::class, 'health_center_id', 'health_center_id');
    }

    public function appointments()
    {
        return $this->hasMany(Appointment::class, 'health_center_id', 'health_center_id');
    }
}
