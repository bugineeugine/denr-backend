<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('permits', function (Blueprint $table) {
            $table->softDeletes();
            $table->uuid('archived_by')->nullable();
            $table->string('archive_reason')->nullable();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->softDeletes();
            $table->uuid('archived_by')->nullable();
            $table->string('archive_reason')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('permits', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropColumn(['archived_by', 'archive_reason']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropColumn(['archived_by', 'archive_reason']);
        });
    }
};
