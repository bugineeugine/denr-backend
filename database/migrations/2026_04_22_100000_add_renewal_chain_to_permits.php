<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('permits', function (Blueprint $table) {
            $table->uuid('renewed_from')->nullable()->after('created_by');
            $table->unsignedInteger('renewal_count')->default(0)->after('renewed_from');
            $table->index('renewed_from');
        });
    }

    public function down(): void
    {
        Schema::table('permits', function (Blueprint $table) {
            $table->dropIndex(['renewed_from']);
            $table->dropColumn(['renewed_from', 'renewal_count']);
        });
    }
};
