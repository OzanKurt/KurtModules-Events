<?php

declare(strict_types=1);

namespace Database\Factories\Kurt\Modules\Events\Flow;

use Illuminate\Database\Eloquent\Factories\Factory;
use Kurt\Modules\Events\Catalog\Models\Event;
use Kurt\Modules\Events\Flow\Models\SponsorTier;

/**
 * @extends Factory<SponsorTier>
 */
class SponsorTierFactory extends Factory
{
    /** @var class-string<SponsorTier> */
    protected $model = SponsorTier::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = $this->faker->randomElement(['Gold', 'Silver', 'Bronze']).'-'.$this->faker->unique()->randomNumber(4);

        return [
            'event_id' => Event::factory(),
            'slug' => str($name)->slug()->toString(),
            'name' => $name,
            'price_minor' => 100000,
            'currency' => 'USD',
            'comp_ticket_quota' => 0,
            'position' => 0,
        ];
    }
}
