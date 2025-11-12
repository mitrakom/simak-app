<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Institusi>
 */
class InstitusiFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $colors = ['blue', 'purple', 'green', 'red', 'indigo', 'teal', 'orange', 'pink', 'cyan', 'emerald'];

        return [
            'nama' => $this->faker->company(),
            'slug' => $this->faker->slug(),
            'feeder_url' => $this->faker->url(),
            'feeder_username' => $this->faker->userName(),
            'feeder_password' => $this->faker->password(),
            'theme_primary_color' => $this->faker->randomElement($colors),
            'theme_secondary_color' => $this->faker->randomElement($colors),
            'theme_accent_color' => $this->faker->randomElement($colors),
            'theme_mode' => $this->faker->randomElement(['light', 'dark', 'auto']),
        ];
    }
}
