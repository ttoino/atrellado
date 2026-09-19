<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\ThreadComment;
use Illuminate\Database\Seeder;

class ThreadCommentSeeder extends Seeder
{
    const MIN_COMMENTS_PER_THREAD = 0;

    const MAX_COMMENTS_PER_THREAD = 5;

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

            $threads = $project->threads;
            $projectMembers = $project->users;

            foreach ($threads as $thread) {
                ThreadComment::factory()
                    ->count($faker->numberBetween(ThreadCommentSeeder::MIN_COMMENTS_PER_THREAD, ThreadCommentSeeder::MAX_COMMENTS_PER_THREAD))
                    ->for($thread)
                    ->withAuthors($projectMembers)
                    ->create();
            }
        }
    }
}
