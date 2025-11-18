<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('permits', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('permit_type');
            $table->string('permit_no')->unique();
            $table->string('land_owner');
            $table->string('contact_no');
            $table->string('location');
            $table->string('area');
            $table->string('species');
            $table->string('total_volume');
            $table->string('plate_no');
            $table->string('destination');
            $table->string('expiry_date')->default('');
            $table->string('grand_total');
            $table->string('remaning_balance');
            $table->string('issued_date')->default('');
            $table->string('status')->default('Active');
            $table->string('qrcode');
            $table->float('lng');
            $table->float('lat');
             $table->float('noTruckloads');
              $table->float('verificationFee');
               $table->float('oathFee');
                $table->float('inspectionFee');
                $table->float('totalAmountDue');
                 $table->string('requestLetter');
                  $table->string('certificateBarangay');
                   $table->string('orCr');
                    $table->string('driverLicense');
                    $table->string('otherDocuments')->default('');
            $table->timestamps();
        });
    }



    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('permits');
    }
};
