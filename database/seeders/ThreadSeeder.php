<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\Thread;
use Illuminate\Database\Seeder;

class ThreadSeeder extends Seeder
{
    const MIN_THREADS_PER_PROJECT = 10;

    const MAX_THREADS_PER_PROJECT = 20;

    const AUTHOR_PERCENTAGE = 0.4;

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
            $projectMembers = $project->users;

            $author = $projectMembers->random($projectMembers->count() * ThreadSeeder::AUTHOR_PERCENTAGE);

            Thread::factory()
                ->count($faker->numberBetween(ThreadSeeder::MIN_THREADS_PER_PROJECT, ThreadSeeder::MAX_THREADS_PER_PROJECT))
                ->for($project)
                ->withAuthors($author)
                ->create();
        }
    }
}
