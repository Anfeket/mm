<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\TagCategory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Tag>
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
