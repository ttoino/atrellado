<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        Schema::table('project', function (Blueprint $table) {
            $table->fullText(['name', 'description'])->language('english');
        });

        Schema::table('task', function (Blueprint $table) {
            $table->fullText(['name', 'description'])->language('english');
        });
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        Schema::table('project', function (Blueprint $table) {
            $table->dropFullText(['name', 'description']);
        });

        Schema::table('task', function (Blueprint $table) {
            $table->dropFullText(['name', 'description']);
        });
    }
};
