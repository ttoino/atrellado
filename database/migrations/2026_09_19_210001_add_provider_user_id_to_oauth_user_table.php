<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('oauth_user', function (Blueprint $table) {
            // Nullable: existing rows predate token encryption and cannot be
            // reverse-resolved to a provider id; they stay null until the
            // user's next OAuth login stamps it.
            $table->string('provider_user_id')->nullable()->after('provider_type');
        });
    }

    public function down(): void
    {
        Schema::table('oauth_user', function (Blueprint $table) {
            $table->dropColumn('provider_user_id');
        });
    }
};
