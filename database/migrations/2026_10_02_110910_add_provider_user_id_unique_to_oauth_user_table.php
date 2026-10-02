<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('oauth_user', function (Blueprint $table) {
            $table->unique(['provider_type', 'provider_user_id']);
        });
    }

    public function down(): void
    {
        Schema::table('oauth_user', function (Blueprint $table) {
            $table->dropUnique(['provider_type', 'provider_user_id']);
        });
    }
};
