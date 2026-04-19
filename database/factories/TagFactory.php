<?php

namespace Database\Factories;

use App\Models\Tag;
use App\TagCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Tag>
 */
class TagFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->word(),
            'category' => $this->faker->randomElement(TagCategory::cases())->value,
            'description' => $this->faker->sentence(),
        ];
    }

    public function general(): static
    {
        return $this->state(['category' => TagCategory::General]);
    }

    public function category(TagCategory $category): static
    {
        return $this->state(['category' => $category]);
    }
}
