<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
class Permit extends Model
{
    use HasFactory; use HasUuids;


    protected $casts = [
    'lng' => 'float',
    'lat' => 'float',
    'noTruckloads' => 'integer',
    'verificationFee' => 'float',
    'oathFee' => 'float',
    'inspectionFee' => 'float',
    'totalAmountDue' => 'float',
];

    // Allow mass assignment for these fields
    protected $fillable = [
        'permit_type',
        'permit_no',
        'land_owner',
        'contact_no',
        'location',
        'area',
        'species',
        'total_volume',
        'plate_no',
        'destination',
        'expiry_date',
        'grand_total',
        'remaning_balance',
        'issued_date',
        'lng',
        'lat',
        'status',
        'qrcode',
        'created_by',
        'noTruckloads',
        'verificationFee',
        'oathFee',
        'inspectionFee',
        'totalAmountDue',
        'requestLetter',
        'certificateBarangay',
        'orCr',
        'driverLicense',
        'otherDocuments'
    ];
       public function creator()
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }
}
