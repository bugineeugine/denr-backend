<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
class CitizenCharter extends Model
{
        use HasFactory; use HasUuids;
      protected $table = 'citizen_charter';
          protected $fillable = [
            'citizen_no',
        'type_transaction',
        'requestLetter',
        'barangayCertification',
        'treeCuttingPermit',
        'orCr',
        'transportAgreement',
        'spa',
        'status',
        'created_by',
        'steps'


    ];
        public function creator()
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }
}
