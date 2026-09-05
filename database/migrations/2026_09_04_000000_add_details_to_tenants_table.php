<?php

declare(strict_types=1);

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
        Schema::table('tenants', function (Blueprint $table): void {
            $table->string('name');
            $table->string('ntn')->nullable();
            $table->string('strn')->nullable();
            $table->string('contact_name')->nullable();
            $table->string('contact_phone')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('currency')->default('PKR');
            $table->string('timezone')->default('Asia/Karachi');
            $table->boolean('is_active')->default(true);
            $table->timestamp('trial_ends_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table): void {
            $table->dropColumn([
                'name',
                'ntn',
                'strn',
                'contact_name',
                'contact_phone',
                'contact_email',
                'currency',
                'timezone',
                'is_active',
                'trial_ends_at',
            ]);
        });
    }
};
