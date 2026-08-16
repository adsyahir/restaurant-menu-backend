<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class SubscriptionController extends Controller
{
    /**
     * The signed-in user's account subscription: plan, status, trial, limits
     * and how many restaurants they currently own.
     */
    public function show(Request $request): JsonResponse
    {
        return response()->json($this->payload($request->user()));
    }

    /**
     * Change the account plan.
     *
     * Upgrades take effect immediately. Downgrading below current usage does
     * NOT delete restaurants — the ones beyond the new limit simply become
     * read-only until the user upgrades again or removes them.
     */
    public function update(Request $request): JsonResponse
    {
        $data = $request->validate([
            'plan' => ['required', Rule::in(array_keys(User::PLAN_LIMITS))],
        ]);

        $user = $request->user();
        $user->plan = $data['plan'];
        $user->subscription_status = 'active'; // manual plan changes are not trials
        $user->renews_on = $data['plan'] === 'free' ? null : Carbon::now()->addMonth()->toDateString();
        $user->save();

        return response()->json($this->payload($user->fresh()));
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(User $user): array
    {
        $limit = $user->planLimit();
        $owned = $user->ownedWorkspaces()->count();

        return [
            'plan' => $user->plan,
            'status' => $user->subscription_status,
            'trialEndsAt' => $user->trial_ends_at?->toIso8601String(),
            'renewsOn' => $user->renews_on?->toDateString(),
            'trialExpired' => $user->trialExpired(),
            'limits' => [
                'restaurants' => $limit, // null = unlimited
            ],
            'usage' => [
                'restaurants' => $owned,
            ],
            'canAddRestaurant' => $limit === null || $owned < $limit,
        ];
    }
}
