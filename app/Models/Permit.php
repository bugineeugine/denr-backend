<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;
class Permit extends Model
{
    use HasFactory; use HasUuids; use SoftDeletes;


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
        'typeForestProduct',
        'estimatedVolumeQuantity',
        'typeConveyancePlateNumber',
        'consignee',
        'dateOfTransport',
        'landOwner',
        'contactNumber',
        'species',
        'issued_date',
        'lng',
        'lat',
        'status',
        'qrcode',
        'created_by',
        'expiry_date',
        'oathFee',
        'inspectionFee',
        'totalAmountDue',
        'requestLetter',
        'certificateBarangay',
        'orCr',
        'driverLicense',
        'otherDocuments',
        'status_step',
        'steps',
        'archived_by',
        'archive_reason',
        'renewed_from',
        'renewal_count',
    ];
       public function creator()
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }
}
