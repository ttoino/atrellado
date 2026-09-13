<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

// Schema, indexes and the old business triggers are owned by
// database/migrations + model observers now. populate.sql remains the
// course fixture (PostgreSQL-only, e.g. x'..'::COLOR casts); the factory
// mode is driver-agnostic.
class DatabaseSeeder extends Seeder {

    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run() {
        if (env('DB_LARGE_DATA')) {
            $this->call([
                UserSeeder::class,
                ProjectSeeder::class,
                TagSeeder::class,
                TaskGroupSeeder::class,
                TaskSeeder::class,
                TaskCommentSeeder::class,
                ThreadSeeder::class,
                ThreadCommentSeeder::class,
            ]);
        } else {
            DB::unprepared(file_get_contents('resources/sql/populate.sql'));
        }

        $this->command->info('Database seeded!');
    }
}
