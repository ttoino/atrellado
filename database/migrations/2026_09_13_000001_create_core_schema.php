<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Ported from the PostgreSQL resources/sql/schema.sql + indexes.sql:
// domains become plain column types (TODAY -> useCurrent timestamps,
// COLOR -> integer, PROVIDER -> enum), DEFERRABLE clauses are dropped,
// and the plain indexes are declared inline. The report target CHECK
// stays raw SQL (no fluent builder); pgsql only.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_profile', function (Blueprint $table) {
            $table->id();
            $table->text('name');
            $table->text('email')->unique();
            $table->text('password')->nullable();
            $table->boolean('blocked')->default(false);
            $table->boolean('is_admin')->default(false);
            $table->text('remember_token')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->text('profile_picture_path')->nullable();
        });

        Schema::create('oauth_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('user_profile')->cascadeOnDelete();
            $table->enum('provider_type', ['github', 'google']);
            $table->text('provider_token');
            $table->text('provider_refresh_token')->nullable();
            $table->unique(['provider_type', 'provider_token']);
        });

        Schema::create('project', function (Blueprint $table) {
            $table->id();
            $table->text('name');
            $table->text('description')->nullable();
            $table->timestamp('creation_date')->useCurrent();
            $table->timestamp('edit_date')->nullable();
            $table->boolean('archived')->default(false);
            $table->foreignId('coordinator_id')->constrained('user_profile')->restrictOnDelete();
        });

        Schema::create('task_group', function (Blueprint $table) {
            $table->id();
            $table->text('name');
            $table->text('description')->nullable();
            $table->timestamp('creation_date')->useCurrent();
            $table->integer('position');
            $table->foreignId('project_id')->constrained('project')->cascadeOnDelete();
            $table->unique(['position', 'project_id']);
            $table->index(['project_id', 'position'], 'task_group_project');
        });

        Schema::create('project_timeline_action', function (Blueprint $table) {
            $table->id();
            $table->timestamp('timestamp')->useCurrent();
            $table->text('description');
            $table->foreignId('project_id')->constrained('project')->cascadeOnDelete();
        });

        Schema::create('task', function (Blueprint $table) {
            $table->id();
            $table->text('name');
            $table->text('description')->nullable();
            $table->timestamp('creation_date')->useCurrent();
            $table->timestamp('edit_date')->nullable();
            $table->boolean('completed')->default(false);
            $table->foreignId('creator_id')->nullable()->constrained('user_profile')->nullOnDelete();
            $table->integer('position');
            $table->foreignId('task_group_id')->constrained('task_group')->cascadeOnDelete();
            $table->unique(['position', 'task_group_id']);
            $table->index(['task_group_id', 'position'], 'task_task_group');
        });

        Schema::create('task_comment', function (Blueprint $table) {
            $table->id();
            $table->text('content');
            $table->timestamp('creation_date')->useCurrent();
            $table->timestamp('edit_date')->nullable();
            $table->foreignId('author_id')->nullable()->constrained('user_profile')->nullOnDelete();
            $table->foreignId('task_id')->constrained('task')->cascadeOnDelete();
        });

        Schema::create('tag', function (Blueprint $table) {
            $table->id();
            $table->text('title');
            $table->text('description')->nullable();
            $table->integer('color');
            $table->foreignId('project_id')->constrained('project')->cascadeOnDelete();
        });

        Schema::create('thread', function (Blueprint $table) {
            $table->id();
            $table->text('title');
            $table->text('content');
            $table->timestamp('creation_date')->useCurrent();
            $table->timestamp('edit_date')->nullable();
            $table->foreignId('author_id')->nullable()->constrained('user_profile')->nullOnDelete();
            $table->foreignId('project_id')->constrained('project')->cascadeOnDelete();
        });

        Schema::create('thread_comment', function (Blueprint $table) {
            $table->id();
            $table->text('content');
            $table->timestamp('creation_date')->useCurrent();
            $table->timestamp('edit_date')->nullable();
            $table->foreignId('thread_id')->constrained('thread')->cascadeOnDelete();
            $table->foreignId('author_id')->nullable()->constrained('user_profile')->nullOnDelete();
        });

        Schema::create('notification', function (Blueprint $table) {
            $table->id();
            $table->text('type');
            $table->text('json');
            $table->timestamp('creation_date')->useCurrent();
            $table->timestamp('read_date')->nullable();
            $table->foreignId('notifiable_id')->constrained('user_profile')->cascadeOnDelete();
            $table->index('notifiable_id', 'notification_search_idx');
        });

        Schema::create('report', function (Blueprint $table) {
            $table->id();
            $table->timestamp('creation_date')->useCurrent();
            $table->text('reason');
            $table->foreignId('project_id')->nullable()->constrained('project')->cascadeOnDelete();
            $table->foreignId('user_profile_id')->nullable()->constrained('user_profile')->cascadeOnDelete();
            $table->foreignId('creator_id')->nullable()->constrained('user_profile')->nullOnDelete();
        });

        // Exactly one of project_id / user_profile_id must be set. No fluent
        // check-constraint builder; sqlite cannot ALTER ADD CONSTRAINT.
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE report ADD CONSTRAINT report_exactly_one_target CHECK ((project_id IS NULL)::integer + (user_profile_id IS NULL)::integer = 1)');
        }

        Schema::create('project_member', function (Blueprint $table) {
            $table->foreignId('user_profile_id')->constrained('user_profile')->cascadeOnDelete();
            $table->foreignId('project_id')->constrained('project')->cascadeOnDelete();
            $table->boolean('is_favorite')->default(false);
            $table->primary(['user_profile_id', 'project_id']);
        });

        Schema::create('task_assignee', function (Blueprint $table) {
            $table->foreignId('user_profile_id')->constrained('user_profile')->cascadeOnDelete();
            $table->foreignId('task_id')->constrained('task')->cascadeOnDelete();
            $table->primary(['user_profile_id', 'task_id']);
        });

        Schema::create('task_tag', function (Blueprint $table) {
            $table->foreignId('task_id')->constrained('task')->cascadeOnDelete();
            $table->foreignId('tag_id')->constrained('tag')->cascadeOnDelete();
            $table->primary(['task_id', 'tag_id']);
        });

        Schema::create('password_resets', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('password_resets');
        Schema::dropIfExists('task_tag');
        Schema::dropIfExists('task_assignee');
        Schema::dropIfExists('project_member');
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE report DROP CONSTRAINT IF EXISTS report_exactly_one_target');
        }
        Schema::dropIfExists('report');
        Schema::dropIfExists('notification');
        Schema::dropIfExists('thread_comment');
        Schema::dropIfExists('thread');
        Schema::dropIfExists('tag');
        Schema::dropIfExists('task_comment');
        Schema::dropIfExists('task');
        Schema::dropIfExists('project_timeline_action');
        Schema::dropIfExists('task_group');
        Schema::dropIfExists('project');
        Schema::dropIfExists('oauth_user');
        Schema::dropIfExists('user_profile');
    }
};
