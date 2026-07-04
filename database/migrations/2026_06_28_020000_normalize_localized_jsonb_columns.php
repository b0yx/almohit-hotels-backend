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
        $this->normalizeColumn('facility_categories', 'name', 'varchar(100)', 'name_ar', false);
        $this->normalizeColumn('facilities', 'name', 'varchar(255)', 'name_ar', false);
        $this->normalizeColumn('facilities', 'short_description', 'varchar(255)', 'short_description_ar', false);
        $this->normalizeColumn('facilities', 'description', 'text', 'description_ar', true);

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
            'facility_categories_name_en_unique',
            'facilities_name_en_unique',
        ] as $index) {
            DB::statement('DROP INDEX IF EXISTS '.$this->quoteIdentifier($index));
        }
    }

    private function createScalarUniqueIndexes(): void
    {
        if (Schema::hasTable('room_types')) {
            DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS room_types_hotel_id_name_unique ON "room_types" ("hotel_id", "name")');
        }

        if (Schema::hasTable('facility_categories')) {
            DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS facility_categories_name_unique ON "facility_categories" ("name")');
        }

        if (Schema::hasTable('facilities')) {
            DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS facilities_name_unique ON "facilities" ("name")');
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
