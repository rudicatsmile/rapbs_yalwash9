<?php

namespace Database\Factories;

use App\Models\Department;
use App\Models\FinancialRecord;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\FinancialRecord>
 */
class FinancialRecordFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            // Create a department on the fly or pick one? Factory usually creates new dependencies.
            // But Department model exists?
            'department_id' => Department::firstOrCreate(['name' => 'General'])->id,
            'record_date' => now(),
            'record_name' => $this->faker->sentence(3),
            'income_amount' => 1000000,
            'income_percentage' => 5,
            'income_fixed' => 950000,
            'income_bos' => 0,
            'income_total' => 950000,
            'total_expense' => 0,
            'status' => true,
        ];
    }
}
