<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'course_id' => Course::factory(),
            'amount_cents' => 2999,
            'currency' => 'eur',
            'payment_method' => 'stripe',
            'status' => 'paid',
            'paid_at' => now(),
        ];
    }
}
