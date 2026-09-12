<?php

namespace Database\Factories;

use App\Models\Page;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Page>
 */
class PageFactory extends Factory
{
    protected $model = Page::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->sentence(3);

        return [
            'slug' => Str::slug($title).'-'.fake()->unique()->numberBetween(100, 99999),
            'title' => $title,
            'excerpt' => fake()->sentence(),
            'body' => fake()->paragraphs(3, true),
            'meta_description' => fake()->sentence(),
            'is_published' => true,
            'show_in_footer' => false,
            'position' => 0,
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => ['is_published' => false]);
    }

    public function inFooter(): static
    {
        return $this->state(fn (array $attributes) => ['show_in_footer' => true]);
    }
}
