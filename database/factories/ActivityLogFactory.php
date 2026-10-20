<?php

namespace Database\Factories;

use App\Enums\ActivityAction;
use App\Models\ActivityLog;
use App\Models\Card;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ActivityLog>
 */
class ActivityLogFactory extends Factory
{
    public function definition(): array
    {
        return [
            'subject_type' => (new Card)->getMorphClass(),
            'subject_id' => Card::factory(),
            'actor_id' => User::factory()->moderator(),
            'action' => ActivityAction::Approved,
            'before' => ['status' => 'in_moderation'],
            'after' => ['status' => 'published'],
            'comment' => null,
        ];
    }
}
