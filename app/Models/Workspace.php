<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Workspace extends Model
{
    /** Auto-generate a UUID for the `uuid` column on create. */
    use HasFactory;

    use HasUuids;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'slug',
        'emoji',
        'cuisine',
        'address',
        'postcode',
        'city',
        'state',
        'state_id',
        'city_id',
        'postcode_id',
        'country_code',
        'currency',
        'timezone',
        'plan',
        'subscription_status',
        'renews_on',
        'owner_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'renews_on' => 'date',
        ];
    }

    /**
     * Mirror the migration's column defaults.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'emoji' => '🍜',
        'currency' => 'USD',
        'timezone' => 'UTC',
        'plan' => 'free',
    ];

    /**
     * Columns that should be generated as UUIDs.
     *
     * Keeps the auto-incrementing `id` as the primary key and only fills `uuid`.
     *
     * @return array<int, string>
     */
    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    /**
     * Bind route params (e.g. /api/workspaces/{workspace}) on the opaque UUID,
     * never the internal bigint id.
     */
    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    /**
     * The user who created / owns the workspace.
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * Read-only when the owner's subscription doesn't cover this restaurant:
     * either their free trial has lapsed, or it falls beyond their plan's
     * restaurant limit (e.g. after a downgrade). Writes are blocked; reads stay.
     */
    public function isLocked(): bool
    {
        $owner = $this->owner;

        if ($owner === null) {
            return false;
        }

        if ($owner->trialExpired()) {
            return true;
        }

        if ($owner->planLimit() === null) {
            return false;
        }

        return ! $owner->activeOwnedWorkspaceIds()->contains($this->id);
    }

    /**
     * Members of this workspace (with their per-workspace role).
     */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'workspace_user')
            ->withPivot('role', 'is_active')
            ->withTimestamps();
    }

    /**
     * Saved payment methods (local — no Stripe).
     */
    public function paymentMethods(): HasMany
    {
        return $this->hasMany(PaymentMethod::class);
    }

    /**
     * Billing history.
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    // Structured location (Malaysia). Null for other countries — which use the
    // free-text `state` / `city` / `postcode` string columns instead.
    // Named *Record to avoid clashing with those same-named string attributes.

    public function stateRecord(): BelongsTo
    {
        return $this->belongsTo(State::class, 'state_id');
    }

    public function cityRecord(): BelongsTo
    {
        return $this->belongsTo(City::class, 'city_id');
    }

    public function postcodeRecord(): BelongsTo
    {
        return $this->belongsTo(Postcode::class, 'postcode_id');
    }
}
