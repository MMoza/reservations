<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->decimal('tax_amount', 10, 2)->default(0)->after('discount_reason');
            $table->string('tax_rate')->nullable()->after('tax_amount');
            $table->decimal('commission_amount', 10, 2)->default(0)->after('tax_rate');
        });
    }

    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropColumn(['tax_amount', 'tax_rate', 'commission_amount']);
        });
    }
};
