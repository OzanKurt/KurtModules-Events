<?php

declare(strict_types=1);

namespace Database\Factories\Kurt\Modules\Events\Flow;

use Illuminate\Database\Eloquent\Factories\Factory;
use Kurt\Modules\Events\Catalog\Models\Event;
use Kurt\Modules\Events\Flow\Enums\SponsorStatus;
use Kurt\Modules\Events\Flow\Models\Sponsor;
use Kurt\Modules\Events\Flow\Models\SponsorTier;

/**
 * @extends Factory<Sponsor>
 */
class SponsorFactory extends Factory
{
    /** @var class-string<Sponsor> */
    protected $model = Sponsor::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'sponsor_tier_id' => SponsorTier::factory(),
            'name' => $this->faker->company(),
            'status' => SponsorStatus::Pending,
            'position' => 0,
        ];
    }

    public function active(): static
    {
        return $this->state(fn () => ['status' => SponsorStatus::Active]);
    }
}
