<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->decimal('early_booking_discount_amount', 10, 2)->default(0)->after('commission_amount');
            $table->decimal('seasonal_surcharge_amount', 10, 2)->default(0)->after('early_booking_discount_amount');
        });
    }

    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropColumn(['early_booking_discount_amount', 'seasonal_surcharge_amount']);
        });
    }
};
