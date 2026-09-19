<?php

use App\Models\Project;
use App\Models\Task;
use App\Models\TaskComment;
use App\Models\Thread;
use App\Models\ThreadComment;
use App\Models\User;
use App\Notifications\ProjectArchived;
use App\Notifications\ProjectDeleted;
use App\Notifications\ProjectInvite;
use App\Notifications\ProjectRemoved;
use App\Notifications\TaskAssigned;
use App\Notifications\TaskCommented;
use App\Notifications\TaskCompleted;
use App\Notifications\ThreadCommented;
use App\Notifications\ThreadNew;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->text('type');
            $table->morphs('notifiable');
            $table->json('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });

        $this->migrateLegacyRows();

        Schema::dropIfExists('notification');
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');

        // The payload transformation is one-way; the legacy table comes back
        // empty, so rolling back loses every notification.
        Schema::create('notification', function (Blueprint $table) {
            $table->id();
            $table->text('type');
            $table->text('json');
            $table->timestamp('creation_date')->useCurrent();
            $table->timestamp('read_date')->nullable();
            $table->foreignId('notifiable_id')->constrained('user_profile')->cascadeOnDelete();
            $table->index('notifiable_id', 'notification_search_idx');
        });
    }

    // Legacy payloads held "model:Class:id" references resolved lazily at read
    // time; rewrite them into the denormalized arrays the notification classes
    // now emit, by re-running toArray against the resolved models.
    private function migrateLegacyRows(): void
    {
        if (! Schema::hasTable('notification')) {
            return;
        }

        DB::table('notification')->orderBy('id')->chunk(100, function ($rows) {
            foreach ($rows as $row) {
                $data = $this->mapLegacyPayload($row->type, json_decode($row->json, true) ?: []);

                if ($data === null) {
                    continue;
                }

                DB::table('notifications')->insert([
                    'id' => (string) Str::uuid(),
                    'type' => $row->type,
                    'notifiable_type' => User::class,
                    'notifiable_id' => $row->notifiable_id,
                    'data' => json_encode($data),
                    'read_at' => $row->read_date,
                    'created_at' => $row->creation_date,
                    'updated_at' => $row->creation_date,
                ]);
            }
        });
    }

    // Rows whose root model is already gone cannot be denormalized and only
    // rendered as dead links, so they are dropped. Project-rooted types are
    // the exception: the old UI showed a "Deleted project" placeholder.
    private function mapLegacyPayload(string $type, array $json): ?array
    {
        return match ($type) {
            ProjectInvite::class => $this->mapProjectInvite($json),
            ProjectArchived::class => $this->mapProjectRooted($json, ProjectArchived::class),
            ProjectRemoved::class => $this->mapProjectRooted($json, ProjectRemoved::class),
            ProjectDeleted::class => ['project_name' => $json['project_name'] ?? 'Deleted project'],
            TaskAssigned::class => $this->mapTaskAssigned($json),
            TaskCommented::class => $this->mapModelRooted($json, 'comment', TaskComment::class, TaskCommented::class),
            TaskCompleted::class => $this->mapModelRooted($json, 'task', Task::class, TaskCompleted::class),
            ThreadNew::class => $this->mapModelRooted($json, 'thread', Thread::class, ThreadNew::class),
            ThreadCommented::class => $this->mapModelRooted($json, 'thread_comment', ThreadComment::class, ThreadCommented::class),
            default => $json,
        };
    }

    private function mapProjectInvite(array $json): array
    {
        $project = Project::find($this->referenceId($json['project'] ?? null));

        if ($project) {
            return (new ProjectInvite($json['url'] ?? '', $project))->toArray(null);
        }

        return [
            'project_id' => $this->referenceId($json['project'] ?? null),
            'project_name' => 'Deleted project',
            'url' => $json['url'] ?? '',
        ];
    }

    private function mapProjectRooted(array $json, string $notificationClass): ?array
    {
        $id = $this->referenceId($json['project'] ?? null);
        $project = Project::find($id);

        if ($project) {
            return (new $notificationClass($project))->toArray(null);
        }

        if ($id === null) {
            return null;
        }

        return [
            'project_id' => $id,
            'project_name' => 'Deleted project',
            'url' => route('project', ['project' => $id]),
        ];
    }

    private function mapTaskAssigned(array $json): ?array
    {
        $task = Task::find($this->referenceId($json['task'] ?? null));

        if (! $task) {
            return null;
        }

        // The payload never mentions the assigner; a placeholder instance
        // satisfies the constructor when the user row is gone.
        $assigner = User::find($this->referenceId($json['assigner'] ?? null)) ?? new User;

        return (new TaskAssigned($task, $assigner))->toArray(null);
    }

    private function mapModelRooted(array $json, string $key, string $modelClass, string $notificationClass): ?array
    {
        $model = $modelClass::find($this->referenceId($json[$key] ?? null));

        return $model ? (new $notificationClass($model))->toArray(null) : null;
    }

    private function referenceId(mixed $value): ?int
    {
        if (is_string($value) && str_starts_with($value, 'model:')) {
            return (int) Str::afterLast($value, ':');
        }

        return null;
    }
};
