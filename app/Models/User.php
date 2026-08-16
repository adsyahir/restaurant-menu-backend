<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'email', 'password', 'current_workspace_id', 'plan', 'subscription_status', 'trial_ends_at', 'renews_on'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * How many restaurants (workspaces) each plan may own.
     * null = unlimited.
     *
     * @var array<string, int|null>
     */
    public const PLAN_LIMITS = [
        'free' => 1,
        'pro' => 5,
        'business' => null,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'trial_ends_at' => 'datetime',
            'renews_on' => 'date',
        ];
    }

    /**
     * Restaurant cap for the user's current plan (null = unlimited).
     */
    public function planLimit(): ?int
    {
        // Note: a legit null value (business = unlimited) must survive, so this
        // cannot use `?? 1` — that would turn "unlimited" into a limit of 1.
        return array_key_exists($this->plan, self::PLAN_LIMITS)
            ? self::PLAN_LIMITS[$this->plan]
            : 1;
    }

    /**
     * Workspaces this user owns (owner_id). Distinct from membership.
     */
    public function ownedWorkspaces(): HasMany
    {
        return $this->hasMany(Workspace::class, 'owner_id');
    }

    /**
     * True once a free-plan trial has lapsed. Paid plans never "expire" here.
     */
    public function trialExpired(): bool
    {
        return $this->plan === 'free'
            && $this->subscription_status === 'trialing'
            && $this->trial_ends_at !== null
            && $this->trial_ends_at->isPast();
    }

    /**
     * The ids of the owner's still-active workspaces: the oldest N (N = plan
     * limit) stay active; any beyond the limit are considered locked. Returns
     * all owned ids when the plan is unlimited.
     *
     * @return Collection<int, int>
     */
    public function activeOwnedWorkspaceIds(): Collection
    {
        $query = $this->ownedWorkspaces()->orderBy('created_at')->orderBy('id');

        $limit = $this->planLimit();
        if ($limit !== null) {
            $query->limit($limit);
        }

        return $query->pluck('id');
    }

    /**
     * Workspaces this user belongs to (with their per-workspace role).
     */
    public function workspaces(): BelongsToMany
    {
        return $this->belongsToMany(Workspace::class, 'workspace_user')
            ->withPivot('role', 'is_active')
            ->withTimestamps();
    }

    /**
     * The workspace the user is currently viewing.
     */
    public function currentWorkspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class, 'current_workspace_id');
    }
}
