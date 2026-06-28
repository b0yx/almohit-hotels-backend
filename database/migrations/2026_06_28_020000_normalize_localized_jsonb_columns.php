<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        $this->dropLegacyJsonbIndexes();

        $this->normalizeColumn('hotels', 'name', 'varchar(255)', 'name_ar', false);
        $this->normalizeColumn('hotels', 'description', 'text', 'description_ar', true);
        $this->normalizeColumn('hotels', 'short_description', 'varchar(300)', 'short_description_ar', false);
        $this->normalizeColumn('room_types', 'name', 'varchar(255)', 'name_ar', false);
        $this->normalizeColumn('room_types', 'description', 'text', 'description_ar', true);
        $this->normalizeColumn('hotel_amenities', 'name', 'varchar(100)', 'name_ar', false);
        $this->normalizeColumn('hotel_services', 'name', 'varchar(255)', 'name_ar', false);
        $this->normalizeColumn('hotel_services', 'short_description', 'varchar(255)', 'short_description_ar', false);
        $this->normalizeColumn('hotel_services', 'description', 'text', 'description_ar', true);

        $this->createScalarUniqueIndexes();
    }

    public function down(): void
    {
        // Intentionally irreversible: this migration converts legacy jsonb localized
        // columns into the scalar schema expected by the application migrations.
    }

    private function dropLegacyJsonbIndexes(): void
    {
        foreach ([
            'room_types_hotel_id_name_en_unique',
            'hotel_amenities_name_en_unique',
            'hotel_services_hotel_id_name_en_unique',
        ] as $index) {
            DB::statement('DROP INDEX IF EXISTS '.$this->quoteIdentifier($index));
        }
    }

    private function createScalarUniqueIndexes(): void
    {
        if (Schema::hasTable('room_types')) {
            DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS room_types_hotel_id_name_unique ON "room_types" ("hotel_id", "name")');
        }

        if (Schema::hasTable('hotel_amenities')) {
            DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS hotel_amenities_name_unique ON "hotel_amenities" ("name")');
        }

        if (Schema::hasTable('hotel_services')) {
            DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS hotel_services_hotel_id_name_unique ON "hotel_services" ("hotel_id", "name")');
        }
    }

    private function normalizeColumn(string $table, string $column, string $targetType, ?string $arabicColumn, bool $nullable): void
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
            return;
        }

        if (! $this->isJsonbColumn($table, $column)) {
            return;
        }

        $tableName = $this->quoteIdentifier($table);
        $columnName = $this->quoteIdentifier($column);

        if ($arabicColumn && Schema::hasColumn($table, $arabicColumn)) {
            $arabicName = $this->quoteIdentifier($arabicColumn);
            DB::statement("UPDATE {$tableName} SET {$arabicName} = COALESCE(NULLIF({$arabicName}, ''), NULLIF(({$columnName}::jsonb)->>'ar', '')) WHERE jsonb_typeof({$columnName}::jsonb) = 'object' AND NULLIF(({$columnName}::jsonb)->>'ar', '') IS NOT NULL");
        }

        $fallback = $nullable ? 'NULL' : "''";
        $extracted = "CASE WHEN {$columnName} IS NULL THEN {$fallback} WHEN jsonb_typeof({$columnName}::jsonb) = 'object' THEN COALESCE(NULLIF(({$columnName}::jsonb)->>'en', ''), NULLIF(({$columnName}::jsonb)->>'ar', ''), {$fallback}) ELSE NULLIF({$columnName}::jsonb #>> '{}', '') END";
        if (! $nullable) {
            $extracted = "COALESCE({$extracted}, '')";
        }

        DB::statement("ALTER TABLE {$tableName} ALTER COLUMN {$columnName} TYPE {$targetType} USING {$extracted}");
    }

    private function isJsonbColumn(string $table, string $column): bool
    {
        $result = DB::selectOne(
            'select data_type from information_schema.columns where table_schema = current_schema() and table_name = ? and column_name = ?',
            [$table, $column]
        );

        return ($result?->data_type ?? null) === 'jsonb';
    }

    private function quoteIdentifier(string $identifier): string
    {
        return '"'.str_replace('"', '""', $identifier).'"';
    }
};
