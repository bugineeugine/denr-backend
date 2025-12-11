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
        Schema::create('history_approved', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('permit_id');
            $table->string('action');
            $table->uuid('approved_by');
             $table->integer('steps');
             $table->timestamps();
            $table->foreign('approved_by')
            ->references('id')
            ->on('users')
            ->onDelete('cascade');


        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('history_approved');
    }
};
