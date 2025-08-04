<?php

namespace Database\Factories;

use App\Models\Translation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Translation>
 */
class TranslationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'key' => $this->faker->unique()->word(),
            'locale' => $this->faker->randomElement(['en', 'es', 'fr', 'de', 'it']),
            'value' => $this->faker->sentence(),
            'tag' => $this->faker->optional()->randomElement(['common', 'greeting', 'error', 'success', 'warning']),
        ];
    }

    /**
     * Indicate that the translation is for English locale.
     */
    public function english(): static
    {
        return $this->state(fn (array $attributes) => [
            'locale' => 'en',
        ]);
    }

    /**
     * Indicate that the translation is for Spanish locale.
     */
    public function spanish(): static
    {
        return $this->state(fn (array $attributes) => [
            'locale' => 'es',
        ]);
    }

    /**
     * Indicate that the translation has a specific tag.
     */
    public function tagged(string $tag): static
    {
        return $this->state(fn (array $attributes) => [
            'tag' => $tag,
        ]);
    }

    /**
     * Indicate that the translation is for common usage.
     */
    public function common(): static
    {
        return $this->state(fn (array $attributes) => [
            'tag' => 'common',
        ]);
    }

    /**
     * Indicate that the translation is for greeting.
     */
    public function greeting(): static
    {
        return $this->state(fn (array $attributes) => [
            'tag' => 'greeting',
        ]);
    }
}
