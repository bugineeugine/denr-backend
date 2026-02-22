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
            $table->string('permit_type')->default('Transport');
            $table->string('permit_no')->unique();
            $table->string('typeForestProduct');
            $table->string('estimatedVolumeQuantity');
            $table->string('typeConveyancePlateNumber');
            $table->string('consignee');
            $table->string('species');
            $table->string('landOwner');
            $table->string('contactNumber');
            $table->string('dateOfTransport');
            $table->string('expiry_date')->default('');
            $table->string('issued_date')->default('');
            $table->string('status')->default('Pending');
            $table->string('qrcode');
            $table->float('lng');
            $table->float('lat');
            $table->string('status_step')->default('Forward to PENR/CENR Officer/Deputy CENR Officer');
            $table->integer('steps')->default(0);
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
