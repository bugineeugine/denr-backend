<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('violations', function (Blueprint $table) {
            $table->string('vehicle_plate')->nullable()->after('contact_number');
            $table->text('description')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('violations', function (Blueprint $table) {
            $table->dropColumn('vehicle_plate');
            $table->text('description')->nullable(false)->change();
        });
    }
};
