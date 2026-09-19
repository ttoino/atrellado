<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\TaskComment;
use Illuminate\Database\Seeder;

class TaskCommentSeeder extends Seeder
{
    const MIN_COMMENTS_PER_TASK = 0;

    const MAX_COMMENTS_PER_TASK = 5;

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

        $faker = fake();

        $projects = Project::all();

        foreach ($projects as $project) {

            $tasks = $project->tasks;
            $projectMembers = $project->users;

            foreach ($tasks as $task) {
                TaskComment::factory()
                    ->count($faker->numberBetween(TaskCommentSeeder::MIN_COMMENTS_PER_TASK, TaskCommentSeeder::MAX_COMMENTS_PER_TASK))
                    ->for($task)
                    ->withAuthors($projectMembers)
                    ->create();
            }
        }
    }
}
