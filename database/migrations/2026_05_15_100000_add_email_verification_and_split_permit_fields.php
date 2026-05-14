<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'email_verified_at')) {
                $table->timestamp('email_verified_at')->nullable();
            }
            if (!Schema::hasColumn('users', 'email_verification_code')) {
                $table->string('email_verification_code', 16)->nullable();
            }
            if (!Schema::hasColumn('users', 'email_verification_expires_at')) {
                $table->timestamp('email_verification_expires_at')->nullable();
            }
        });

        Schema::table('violations', function (Blueprint $table) {
            if (!Schema::hasColumn('violations', 'updated_by')) {
                $table->uuid('updated_by')->nullable()->after('recorded_by');
            }
        });

        Schema::table('permits', function (Blueprint $table) {
            if (!Schema::hasColumn('permits', 'estimated_volume')) {
                $table->string('estimated_volume')->nullable()->after('estimatedVolumeQuantity');
            }
            if (!Schema::hasColumn('permits', 'quantity_pcs')) {
                $table->string('quantity_pcs')->nullable()->after('estimated_volume');
            }
            if (!Schema::hasColumn('permits', 'type_conveyance')) {
                $table->string('type_conveyance')->nullable()->after('typeConveyancePlateNumber');
            }
            if (!Schema::hasColumn('permits', 'plate_number')) {
                $table->string('plate_number')->nullable()->after('type_conveyance');
            }
            if (!Schema::hasColumn('permits', 'consignee_name')) {
                $table->string('consignee_name')->nullable()->after('consignee');
            }
            if (!Schema::hasColumn('permits', 'destination')) {
                $table->string('destination')->nullable()->after('consignee_name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('permits', function (Blueprint $table) {
            $table->dropColumn([
                'estimated_volume', 'quantity_pcs',
                'type_conveyance', 'plate_number',
                'consignee_name', 'destination',
            ]);
        });

        Schema::table('violations', function (Blueprint $table) {
            $table->dropColumn('updated_by');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['email_verification_code', 'email_verification_expires_at', 'email_verified_at']);
        });
    }
};
