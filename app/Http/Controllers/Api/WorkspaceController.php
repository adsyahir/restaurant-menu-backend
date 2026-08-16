<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreWorkspaceRequest;
use App\Http\Requests\UpdateWorkspaceRequest;
use App\Http\Resources\WorkspaceResource;
use App\Models\Workspace;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class WorkspaceController extends Controller
{
    /**
     * Currency + timezone defaults per supported country.
     *
     * @var array<string, array{0: string, 1: string}>
     */
    private const LOCALE = [
        'MY' => ['MYR', 'Asia/Kuala_Lumpur'], 'SG' => ['SGD', 'Asia/Singapore'],
        'ID' => ['IDR', 'Asia/Jakarta'], 'TH' => ['THB', 'Asia/Bangkok'],
        'PH' => ['PHP', 'Asia/Manila'], 'VN' => ['VND', 'Asia/Ho_Chi_Minh'],
        'AU' => ['AUD', 'Australia/Sydney'], 'GB' => ['GBP', 'Europe/London'],
        'US' => ['USD', 'America/New_York'], 'AE' => ['AED', 'Asia/Dubai'],
        'IN' => ['INR', 'Asia/Kolkata'], 'JP' => ['JPY', 'Asia/Tokyo'],
    ];

    /**
     * Every workspace the authenticated user belongs to (for the switcher).
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $workspaces = $request->user()
            ->workspaces()
            ->orderBy('name')
            ->get();

        return WorkspaceResource::collection($workspaces);
    }

    /**
     * Create an additional restaurant, enforcing the owner's plan limit.
     */
    public function store(StoreWorkspaceRequest $request): WorkspaceResource
    {
        $user = $request->user();
        $limit = $user->planLimit();

        abort_if(
            $limit !== null && $user->ownedWorkspaces()->count() >= $limit,
            403,
            "Your plan allows up to {$limit} restaurant(s). Upgrade to add more.",
        );

        $data = $request->validated();
        $country = strtoupper($data['country_code']);
        [$currency, $timezone] = self::LOCALE[$country] ?? ['USD', 'UTC'];

        $workspace = DB::transaction(function () use ($user, $data, $country, $currency, $timezone) {
            $workspace = Workspace::create([
                'name' => $data['name'],
                'slug' => $this->uniqueSlug(Str::slug($data['name'])),
                'cuisine' => $data['cuisine'] ?? null,
                'address' => $data['address'] ?? null,
                'country_code' => $country,
                'currency' => $currency,
                'timezone' => $timezone,
                'state' => $data['state'] ?? null,
                'city' => $data['city'] ?? null,
                'postcode' => $data['postcode'] ?? null,
                'owner_id' => $user->id,
            ]);

            $workspace->members()->attach($user->id, ['role' => 'admin']);
            $user->update(['current_workspace_id' => $workspace->id]);

            return $workspace;
        });

        return new WorkspaceResource($workspace);
    }

    private function uniqueSlug(string $base): string
    {
        $base = $base !== '' ? $base : 'restaurant';
        $slug = $base;
        $i = 2;

        while (Workspace::where('slug', $slug)->exists()) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        return $slug;
    }

    /**
     * The authenticated user's current workspace.
     */
    public function show(Request $request): WorkspaceResource
    {
        return new WorkspaceResource($this->current($request));
    }

    /**
     * Switch the active workspace. The user must be a member of the target.
     */
    public function switch(Request $request, Workspace $workspace): WorkspaceResource
    {
        abort_unless(
            $request->user()->workspaces()->whereKey($workspace->id)->exists(),
            403,
            'You are not a member of that workspace.',
        );

        $request->user()->update(['current_workspace_id' => $workspace->id]);

        return new WorkspaceResource($workspace);
    }

    /**
     * Update the current workspace (profile + plan).
     */
    public function update(UpdateWorkspaceRequest $request): WorkspaceResource
    {
        $workspace = $this->current($request);

        $map = [
            'name' => 'name',
            'emoji' => 'emoji',
            'cuisine' => 'cuisine',
            'address' => 'address',
            'state' => 'state',
            'city' => 'city',
            'postcode' => 'postcode',
            'timezone' => 'timezone',
            'currency' => 'currency',
            'plan' => 'plan',
            'subscriptionStatus' => 'subscription_status',
            'renewsOn' => 'renews_on',
        ];

        $data = $request->validated();

        foreach ($map as $input => $column) {
            if (array_key_exists($input, $data)) {
                $workspace->{$column} = $data[$input];
            }
        }

        $workspace->save();

        return new WorkspaceResource($workspace);
    }

    private function current(Request $request): Workspace
    {
        return Workspace::findOrFail($request->user()->current_workspace_id);
    }
}
