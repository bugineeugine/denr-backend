<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Violation extends Model
{
    use HasFactory; use HasUuids;

    protected $casts = [
        'lat' => 'float',
        'lng' => 'float',
        'date_recorded' => 'date',
        'resolved_at' => 'date',
    ];

    protected $fillable = [
        'permit_id',
        'violator_name',
        'contact_number',
        'vehicle_plate',
        'location',
        'lat',
        'lng',
        'violation_type',
        'severity',
        'description',
        'date_recorded',
        'resolved_at',
        'status',
        'evidence',
        'recorded_by',
        'updated_by',
    ];

    public function permit()
    {
        return $this->belongsTo(Permit::class, 'permit_id', 'id');
    }

    public function recorder()
    {
        return $this->belongsTo(User::class, 'recorded_by', 'id');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by', 'id');
    }
}
