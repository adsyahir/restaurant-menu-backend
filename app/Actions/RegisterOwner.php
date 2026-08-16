<?php

namespace App\Actions;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class RegisterOwner
{
    /**
     * Create the owner account + their workspace + membership in one transaction.
     *
     * @param  array<string, mixed>  $data  Validated RegisterRequest data.
     */
    public function handle(array $data): User
    {
        return DB::transaction(function () use ($data): User {
            // Subscription lives on the account. Free plans start a 3-month
            // trial; paid plans are active with a monthly renewal.
            $plan = $data['plan'];
            $isFree = $plan === 'free';

            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'], // hashed via the model cast
                'plan' => $plan,
                'subscription_status' => $isFree ? 'trialing' : 'active',
                'trial_ends_at' => $isFree ? Carbon::now()->addMonths(3) : null,
                'renews_on' => $isFree ? null : Carbon::now()->addMonth()->toDateString(),
            ]);

            $countryCode = strtoupper($data['country_code']);

            $workspace = Workspace::create([
                'name' => $data['restaurant_name'],
                'slug' => $this->uniqueSlug($data['slug']),
                'cuisine' => $data['cuisine'] ?? null,
                'address' => $data['address'] ?? null,
                'country_code' => $countryCode,
                'currency' => $this->currencyFor($countryCode),
                'timezone' => $this->timezoneFor($countryCode),
                'plan' => $data['plan'],
                'owner_id' => $user->id,
                // Malaysia: structured selection.
                'state_id' => $data['state_id'] ?? null,
                'city_id' => $data['city_id'] ?? null,
                'postcode_id' => $data['postcode_id'] ?? null,
                // Free text (other countries; also the mirrored names for MY).
                'state' => $data['state'] ?? null,
                'city' => $data['city'] ?? null,
                'postcode' => $data['postcode'] ?? null,
            ]);

            // The creator is the workspace admin/owner.
            $workspace->members()->attach($user->id, ['role' => 'admin']);

            $user->update(['current_workspace_id' => $workspace->id]);

            return $user->setRelation('currentWorkspace', $workspace);
        });
    }

    /**
     * Ensure the subdomain is free, appending -2, -3, … on collision.
     */
    protected function uniqueSlug(string $base): string
    {
        $slug = $base;
        $i = 2;

        while (Workspace::where('slug', $slug)->exists()) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        return $slug;
    }

    /**
     * ISO 4217 currency for a supported country.
     */
    protected function currencyFor(string $countryCode): string
    {
        return [
            'MY' => 'MYR', 'SG' => 'SGD', 'ID' => 'IDR', 'TH' => 'THB',
            'PH' => 'PHP', 'VN' => 'VND', 'AU' => 'AUD', 'GB' => 'GBP',
            'US' => 'USD', 'AE' => 'AED', 'IN' => 'INR', 'JP' => 'JPY',
        ][$countryCode] ?? 'USD';
    }

    /**
     * IANA timezone for a supported country.
     */
    protected function timezoneFor(string $countryCode): string
    {
        return [
            'MY' => 'Asia/Kuala_Lumpur', 'SG' => 'Asia/Singapore', 'ID' => 'Asia/Jakarta',
            'TH' => 'Asia/Bangkok', 'PH' => 'Asia/Manila', 'VN' => 'Asia/Ho_Chi_Minh',
            'AU' => 'Australia/Sydney', 'GB' => 'Europe/London', 'US' => 'America/New_York',
            'AE' => 'Asia/Dubai', 'IN' => 'Asia/Kolkata', 'JP' => 'Asia/Tokyo',
        ][$countryCode] ?? 'UTC';
    }
}
