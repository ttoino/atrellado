<?php

namespace Database\Factories;

use App\Models\Report;
use App\Models\Thread;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Report>
 */
class ThreadFactory extends Factory
{
    const EDITED_THREAD_PERCENTAGE = 0.4;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'title' => $this->faker->sentence(3),
            'content' => $this->faker->paragraph,
            'creation_date' => $this->faker->dateTimeBetween('-5 month'),
            'edit_date' => function ($attributes) {
                return $this->faker->optional(ThreadFactory::EDITED_THREAD_PERCENTAGE)->dateTimeBetween($attributes['creation_date']);
            },
        ];
    }

    public function withAuthors(Collection $author)
    {
        return $this->sequence(
            fn ($sequence) => ['author_id' => $author->random()]
        );
    }

    protected $model = Thread::class;
}
