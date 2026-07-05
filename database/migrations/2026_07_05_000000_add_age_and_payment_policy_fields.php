<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hotel_policies', function (Blueprint $table) {
            $table->text('age_restriction')->nullable()->after('extra_bed_policy');
            $table->text('accepted_payment_methods')->nullable()->after('age_restriction');
            $table->text('age_restriction_ar')->nullable()->after('extra_bed_policy_ar');
            $table->text('accepted_payment_methods_ar')->nullable()->after('age_restriction_ar');
        });
    }

    public function down(): void
    {
        Schema::table('hotel_policies', function (Blueprint $table) {
            $table->dropColumn([
                'age_restriction',
                'accepted_payment_methods',
                'age_restriction_ar',
                'accepted_payment_methods_ar',
            ]);
        });
    }
};
