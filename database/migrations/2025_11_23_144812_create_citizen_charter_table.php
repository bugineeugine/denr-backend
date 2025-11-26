<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('citizen_charter', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('citizen_no');
            $table->string('type_transaction');
            $table->string('requestLetter');
            $table->string('barangayCertification');
            $table->string('treeCuttingPermit');
            $table->string('orCr');
            $table->string('transportAgreement');
            $table->string('spa');
            $table->string('status')->default('Forward to PENR/CENR Officer/Deputy CENR Officer');
            $table->integer('steps')->default(0);
            $table->uuid('created_by');
              $table->foreign('created_by')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('citizen_charter');
    }
};
