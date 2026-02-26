<?php

namespace Database\Factories;

use App\Models\Department;
use App\Models\Realization;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Realization>
 */
class RealizationFactory extends Factory
{
    protected $model = Realization::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'department_id' => Department::factory(),
            'record_date' => $this->faker->date(),
            'record_name' => $this->faker->sentence(3),
            'income_amount' => $this->faker->randomFloat(2, 1000, 100000),
            'income_percentage' => 100,
            'income_fixed' => 0,
            'income_bos' => 0,
            'income_total' => $this->faker->randomFloat(2, 1000, 100000),
            'total_expense' => 0,
            'total_realization' => 0,
            'total_balance' => 0,
            'status' => true,
            'status_realisasi' => false,
            'is_approved_by_bendahara' => false,
        ];
    }
}
