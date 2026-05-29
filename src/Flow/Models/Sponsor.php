<?php

declare(strict_types=1);

namespace Kurt\Modules\Events\Flow\Models;

use Database\Factories\Kurt\Modules\Events\Flow\SponsorFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Kurt\Modules\Core\Concerns\ResolvesUser;
use Kurt\Modules\Events\Catalog\Models\Event;
use Kurt\Modules\Events\Flow\Enums\SponsorStatus;
use Kurt\Modules\Events\Ticketing\Models\Order;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * @property int $id
 * @property int $event_id
 * @property int $sponsor_tier_id
 * @property string $name
 * @property int|null $contact_user_id
 * @property string|null $logo_path
 * @property string|null $website_url
 * @property string|null $blurb
 * @property SponsorStatus $status
 * @property int|null $order_id
 * @property int $position
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
class Sponsor extends Model implements HasMedia
{
    /** @use HasFactory<SponsorFactory> */
    use HasFactory;

    use InteractsWithMedia;
    use ResolvesUser;
    use SoftDeletes;

    protected $table = 'events_sponsors';

    /** @var list<string> */
    protected $fillable = [
        'event_id', 'sponsor_tier_id',
        'name', 'contact_user_id',
        'logo_path', 'website_url', 'blurb',
        'status', 'order_id', 'position',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'status' => SponsorStatus::class,
        'position' => 'integer',
    ];

    /**
     * @return BelongsTo<Event, $this>
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * @return BelongsTo<SponsorTier, $this>
     */
    public function tier(): BelongsTo
    {
        return $this->belongsTo(SponsorTier::class, 'sponsor_tier_id');
    }

    /**
     * @return BelongsTo<Model, $this>
     */
    public function contactUser(): BelongsTo
    {
        return $this->userBelongsTo('contact_user_id');
    }

    /**
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * @return HasMany<SponsorCompTicket, $this>
     */
    public function compTickets(): HasMany
    {
        return $this->hasMany(SponsorCompTicket::class);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('logo')->singleFile();
    }

    protected static function newFactory(): SponsorFactory
    {
        return SponsorFactory::new();
    }
}
