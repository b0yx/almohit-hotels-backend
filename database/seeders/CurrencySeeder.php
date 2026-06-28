<?php

namespace Database\Seeders;

use App\Models\Currency;
use Illuminate\Database\Seeder;

class CurrencySeeder extends Seeder
{
    public function run(): void
    {
        $currencies = [
            ['code' => 'USD', 'name' => 'US Dollar', 'symbol' => '$', 'symbol_position' => 'before', 'decimal_places' => 2, 'thousand_separator' => ',', 'decimal_separator' => '.', 'is_default' => true],
            ['code' => 'EUR', 'name' => 'Euro', 'symbol' => 'EUR', 'symbol_position' => 'after', 'decimal_places' => 2, 'thousand_separator' => ',', 'decimal_separator' => '.'],
            ['code' => 'GBP', 'name' => 'Pound Sterling', 'symbol' => 'GBP', 'symbol_position' => 'before', 'decimal_places' => 2, 'thousand_separator' => ',', 'decimal_separator' => '.'],
            ['code' => 'SAR', 'name' => 'Saudi Riyal', 'symbol' => 'SAR', 'symbol_position' => 'after', 'decimal_places' => 2, 'thousand_separator' => ',', 'decimal_separator' => '.'],
            ['code' => 'AED', 'name' => 'UAE Dirham', 'symbol' => 'AED', 'symbol_position' => 'after', 'decimal_places' => 2, 'thousand_separator' => ',', 'decimal_separator' => '.'],
            ['code' => 'QAR', 'name' => 'Qatari Riyal', 'symbol' => 'QAR', 'symbol_position' => 'after', 'decimal_places' => 2, 'thousand_separator' => ',', 'decimal_separator' => '.'],
            ['code' => 'KWD', 'name' => 'Kuwaiti Dinar', 'symbol' => 'KWD', 'symbol_position' => 'after', 'decimal_places' => 3, 'thousand_separator' => ',', 'decimal_separator' => '.'],
            ['code' => 'OMR', 'name' => 'Omani Rial', 'symbol' => 'OMR', 'symbol_position' => 'after', 'decimal_places' => 3, 'thousand_separator' => ',', 'decimal_separator' => '.'],
            ['code' => 'BHD', 'name' => 'Bahraini Dinar', 'symbol' => 'BHD', 'symbol_position' => 'after', 'decimal_places' => 3, 'thousand_separator' => ',', 'decimal_separator' => '.'],
            ['code' => 'JOD', 'name' => 'Jordanian Dinar', 'symbol' => 'JOD', 'symbol_position' => 'after', 'decimal_places' => 3, 'thousand_separator' => ',', 'decimal_separator' => '.'],
            ['code' => 'EGP', 'name' => 'Egyptian Pound', 'symbol' => 'EGP', 'symbol_position' => 'after', 'decimal_places' => 2, 'thousand_separator' => ',', 'decimal_separator' => '.'],
            ['code' => 'TRY', 'name' => 'Turkish Lira', 'symbol' => 'TRY', 'symbol_position' => 'after', 'decimal_places' => 2, 'thousand_separator' => ',', 'decimal_separator' => '.'],
            ['code' => 'YER', 'name' => 'Yemeni Rial', 'symbol' => 'ر.ي', 'symbol_position' => 'after', 'decimal_places' => 2, 'thousand_separator' => '٬', 'decimal_separator' => '٫'],
            ['code' => 'INR', 'name' => 'Indian Rupee', 'symbol' => 'INR', 'symbol_position' => 'before', 'decimal_places' => 2, 'thousand_separator' => ',', 'decimal_separator' => '.'],
            ['code' => 'PKR', 'name' => 'Pakistani Rupee', 'symbol' => 'PKR', 'symbol_position' => 'before', 'decimal_places' => 2, 'thousand_separator' => ',', 'decimal_separator' => '.'],
            ['code' => 'CNY', 'name' => 'Yuan Renminbi', 'symbol' => 'CNY', 'symbol_position' => 'before', 'decimal_places' => 2, 'thousand_separator' => ',', 'decimal_separator' => '.'],
            ['code' => 'JPY', 'name' => 'Yen', 'symbol' => 'JPY', 'symbol_position' => 'before', 'decimal_places' => 0, 'thousand_separator' => ',', 'decimal_separator' => '.'],
            ['code' => 'CHF', 'name' => 'Swiss Franc', 'symbol' => 'CHF', 'symbol_position' => 'before', 'decimal_places' => 2, 'thousand_separator' => ',', 'decimal_separator' => '.'],
            ['code' => 'AUD', 'name' => 'Australian Dollar', 'symbol' => 'AUD', 'symbol_position' => 'before', 'decimal_places' => 2, 'thousand_separator' => ',', 'decimal_separator' => '.'],
            ['code' => 'CAD', 'name' => 'Canadian Dollar', 'symbol' => 'CAD', 'symbol_position' => 'before', 'decimal_places' => 2, 'thousand_separator' => ',', 'decimal_separator' => '.'],
        ];

        foreach ($currencies as $currency) {
            Currency::query()->updateOrCreate(
                ['code' => $currency['code']],
                array_merge([
                    'symbol' => '',
                    'symbol_position' => 'before',
                    'decimal_places' => 2,
                    'thousand_separator' => ',',
                    'decimal_separator' => '.',
                    'is_default' => false,
                    'is_active' => true,
                ], $currency)
            );
        }

        Currency::query()->where('code', '!=', 'USD')->update(['is_default' => false]);
    }
}
