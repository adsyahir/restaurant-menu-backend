<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Workspace extends Model
{
    /** Auto-generate a UUID for the `uuid` column on create. */
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
        'owner_id',
    ];

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
     * Members of this workspace (with their per-workspace role).
     */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'workspace_user')
            ->withPivot('role')
            ->withTimestamps();
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
