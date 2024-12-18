<?php

namespace Database\Factories;

use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TaskFactory extends Factory
{
    protected $model = Task::class;

    public function definition()
    {
        return [
            'title' => $this->faker->sentence,
            'description' => $this->faker->paragraph,
            'due_date' => $this->faker->optional()->dateTimeBetween('now', '+1 year'),
            'priority' => $this->faker->randomElement(['Urgent', 'High', 'Normal', 'Low']),
            'user_id' => 1,
            'order' => $this->faker->numberBetween(1, 100),
        ];
    }
}
