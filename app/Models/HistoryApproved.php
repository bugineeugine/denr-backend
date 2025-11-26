<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;


class HistoryApproved extends Model
{
       use HasFactory; use HasUuids;

    protected $table = 'history_approved';
        protected $fillable = [
        'citizenCharterId',
        'action',
        'approved_by',
    ];


}
