<?php

namespace Database\Factories;

use App\Enums\CardStatus;
use App\Enums\CardType;
use App\Enums\DeadlineNature;
use App\Models\Card;
use App\Models\PublicFigure;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Card>
 */
class CardFactory extends Factory
{
    public function definition(): array
    {
        $statedOn = fake()->dateTimeBetween('-3 years', '-1 month');

        return [
            'public_figure_id' => PublicFigure::factory(),
            'type' => fake()->randomElement(CardType::cases()),
            'quote' => fake()->sentence(12),
            'context' => fake()->paragraph(),
            'stated_on' => $statedOn,
            'testable_statement' => fake()->sentence(),
            'verdict_criterion' => fake()->sentence(),
            'deadline' => fake()->dateTimeBetween('+1 month', '+3 years'),
            'deadline_nature' => DeadlineNature::Explicit,
            'status' => CardStatus::InModeration,
            'author_id' => User::factory(),
            'author_email' => null,
            'email_confirmed_at' => null,
            'version' => 1,
            'published_at' => null,
        ];
    }

    public function promise(): static
    {
        return $this->state(fn (array $attributes) => ['type' => CardType::Promise]);
    }

    public function prediction(): static
    {
        return $this->state(fn (array $attributes) => ['type' => CardType::Prediction]);
    }

    /**
     * Proposée par un visiteur sans compte, e-mail pas encore confirmé.
     */
    public function anonymous(?string $email = null): static
    {
        return $this->state(fn (array $attributes) => [
            'author_id' => null,
            'author_email' => $email ?? fake()->unique()->safeEmail(),
            'status' => CardStatus::AwaitingEmail,
        ]);
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => CardStatus::Published,
            'published_at' => now(),
        ]);
    }

    public function rejected(string $reason = 'Citation introuvable dans la source.'): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => CardStatus::Rejected,
            'rejection_reason' => $reason,
        ]);
    }

    /**
     * Publiée, échéance dépassée, en attente d'un verdict.
     */
    public function overdue(): static
    {
        return $this->published()->state(fn (array $attributes) => [
            'deadline' => fake()->dateTimeBetween('-1 year', '-1 day'),
        ]);
    }
}
