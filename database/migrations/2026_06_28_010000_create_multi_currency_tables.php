<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('currencies', function (Blueprint $table) {
            $table->id();
            $table->string('code', 3)->unique();
            $table->string('name', 100);
            $table->string('symbol', 16)->default('');
            $table->string('symbol_position', 10)->default('before');
            $table->unsignedTinyInteger('decimal_places')->default(2);
            $table->string('thousand_separator', 8)->default(',');
            $table->string('decimal_separator', 8)->default('.');
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['code'], 'idx_currencies_code');
            $table->index(['is_active'], 'idx_currencies_active');
            $table->index(['is_default'], 'idx_currencies_default');
        });

        Schema::create('exchange_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('from_currency_id')->constrained('currencies')->restrictOnDelete();
            $table->foreignId('to_currency_id')->constrained('currencies')->restrictOnDelete();
            $table->decimal('exchange_rate', 20, 10);
            $table->timestamp('effective_from');
            $table->timestamp('effective_to')->nullable();
            $table->string('source', 80)->default('manual');
            $table->text('notes')->nullable();
            $table->boolean('is_manual')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['from_currency_id', 'to_currency_id', 'effective_from'], 'uniq_exchange_rate_pair_effective_from');
            $table->index(['from_currency_id', 'to_currency_id', 'effective_from'], 'idx_exchange_rates_pair_from');
            $table->index(['from_currency_id', 'to_currency_id', 'effective_to'], 'idx_exchange_rates_pair_to');
            $table->index(['effective_from', 'effective_to'], 'idx_exchange_rates_effective_dates');
            $table->index(['source'], 'idx_exchange_rates_source');
        });

        Schema::table('booking_inquiries', function (Blueprint $table) {
            $table->foreignId('booking_currency_id')->nullable()->after('estimated_total')->constrained('currencies')->nullOnDelete();
            $table->decimal('exchange_rate', 20, 10)->nullable()->after('booking_currency_id');
            $table->decimal('subtotal', 12, 2)->default(0)->after('exchange_rate');
            $table->decimal('tax', 12, 2)->default(0)->after('subtotal');
            $table->decimal('discount', 12, 2)->default(0)->after('tax');
            $table->decimal('total', 12, 2)->default(0)->after('discount');

            $table->index(['booking_currency_id'], 'idx_booking_inquiries_currency');
        });
    }

    public function down(): void
    {
        Schema::table('booking_inquiries', function (Blueprint $table) {
            $table->dropIndex('idx_booking_inquiries_currency');
            $table->dropConstrainedForeignId('booking_currency_id');
            $table->dropColumn(['exchange_rate', 'subtotal', 'tax', 'discount', 'total']);
        });

        Schema::dropIfExists('exchange_rates');
        Schema::dropIfExists('currencies');
    }
};
