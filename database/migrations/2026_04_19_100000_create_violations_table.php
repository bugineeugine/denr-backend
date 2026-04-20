<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('violations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('permit_id')->nullable();
            $table->string('violator_name');
            $table->string('contact_number')->nullable();
            $table->string('location')->nullable();
            $table->float('lat')->nullable();
            $table->float('lng')->nullable();
            $table->string('violation_type');
            $table->string('severity')->default('Low');
            $table->text('description');
            $table->date('date_recorded');
            $table->date('resolved_at')->nullable();
            $table->string('status')->default('Open');
            $table->string('evidence')->nullable();
            $table->uuid('recorded_by');
            $table->timestamps();

            $table->index('permit_id');
            $table->index('status');
            $table->index('severity');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('violations');
    }
};
