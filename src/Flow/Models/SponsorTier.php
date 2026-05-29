<?php

declare(strict_types=1);

namespace Kurt\Modules\Events\Flow\Models;

use Cviebrock\EloquentSluggable\Sluggable;
use Database\Factories\Kurt\Modules\Events\Flow\SponsorTierFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Kurt\Modules\Events\Catalog\Models\Event;
use Kurt\Modules\Events\Ticketing\Models\TicketType;

/**
 * @property int $id
 * @property int $event_id
 * @property string $slug
 * @property string $name
 * @property int $price_minor
 * @property string $currency
 * @property int $comp_ticket_quota
 * @property int|null $comp_ticket_type_id
 * @property array<int, mixed>|null $benefits
 * @property int $position
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
class SponsorTier extends Model
{
    /** @use HasFactory<SponsorTierFactory> */
    use HasFactory;

    use Sluggable;
    use SoftDeletes;

    protected $table = 'events_sponsor_tiers';

    /** @var list<string> */
    protected $fillable = [
        'event_id', 'slug', 'name',
        'price_minor', 'currency',
        'comp_ticket_quota', 'comp_ticket_type_id',
        'benefits', 'position',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'benefits' => 'array',
        'price_minor' => 'integer',
        'comp_ticket_quota' => 'integer',
        'position' => 'integer',
    ];

    /**
     * @return array<string, array<string, mixed>>
     */
    public function sluggable(): array
    {
        return ['slug' => ['source' => 'name']];
    }

    /**
     * @return BelongsTo<Event, $this>
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * @return BelongsTo<TicketType, $this>
     */
    public function compTicketType(): BelongsTo
    {
        return $this->belongsTo(TicketType::class, 'comp_ticket_type_id');
    }

    /**
     * @return HasMany<Sponsor, $this>
     */
    public function sponsors(): HasMany
    {
        return $this->hasMany(Sponsor::class);
    }

    protected static function newFactory(): SponsorTierFactory
    {
        return SponsorTierFactory::new();
    }
}
