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
        $schema = Schema::connection('visitor');

        if (!$schema->hasColumn('visitor', 'plan_delivery_time')) {
            $schema->table('visitor', function (Blueprint $table) {
                $table->time('plan_delivery_time')->nullable()->after('visitor_vehicle');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $schema = Schema::connection('visitor');

        if ($schema->hasColumn('visitor', 'plan_delivery_time')) {
            $schema->table('visitor', function (Blueprint $table) {
                $table->dropColumn('plan_delivery_time');
            });
        }
    }
};

