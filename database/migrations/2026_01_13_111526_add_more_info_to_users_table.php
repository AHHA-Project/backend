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
        Schema::table('users', function (Blueprint $table) {
            $table->string('profile_img')->nullable();
            $table->boolean('is_active')->default(true)->after('email');
            $table->timestamp('deactivated_at')->nullable()->after('is_active');
            $table->unsignedBigInteger('deactivated_by')->nullable()->after('deactivated_at');
            $table->timestamp('activated_at')->nullable()->after('deactivated_by');
            $table->unsignedBigInteger('activated_by')->nullable()->after('activated_at');
            
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            
            // Then drop columns
            $table->dropColumn([
                'profile_img',
                'is_active',
                'deactivated_at',
                'deactivated_by',
                'activated_at',
                'activated_by'
            ]);
        });
    }
};