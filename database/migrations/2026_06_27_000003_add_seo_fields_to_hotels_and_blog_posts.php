<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hotels', function (Blueprint $table) {
            $table->string('meta_title', 60)->nullable()->after('short_description_ar');
            $table->string('meta_description', 160)->nullable()->after('meta_title');
            $table->string('meta_title_ar', 60)->nullable()->after('meta_description');
            $table->string('meta_description_ar', 160)->nullable()->after('meta_title_ar');
        });

        Schema::table('blog_posts', function (Blueprint $table) {
            if (! Schema::hasColumn('blog_posts', 'meta_title_ar')) {
                $table->string('meta_title_ar', 60)->nullable()->after('meta_description');
            }
            if (! Schema::hasColumn('blog_posts', 'meta_description_ar')) {
                $table->string('meta_description_ar', 160)->nullable()->after('meta_title_ar');
            }
        });

        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE blog_posts MODIFY meta_title VARCHAR(60) NULL, MODIFY meta_description VARCHAR(160) NULL');
        } elseif ($driver === 'pgsql') {
            DB::statement('ALTER TABLE blog_posts ALTER COLUMN meta_title TYPE VARCHAR(60), ALTER COLUMN meta_title DROP NOT NULL, ALTER COLUMN meta_description TYPE VARCHAR(160), ALTER COLUMN meta_description DROP NOT NULL');
        }
    }

    public function down(): void
    {
        Schema::table('blog_posts', function (Blueprint $table) {
            $table->dropColumn(['meta_title_ar', 'meta_description_ar']);
        });

        Schema::table('hotels', function (Blueprint $table) {
            $table->dropColumn(['meta_title', 'meta_description', 'meta_title_ar', 'meta_description_ar']);
        });
    }
};
