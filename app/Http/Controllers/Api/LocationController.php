<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\Country;
use App\Models\State;
use Illuminate\Http\JsonResponse;

class LocationController extends Controller
{
    /**
     * States for a country (defaults to Malaysia).
     */
    public function states(string $iso2 = 'MY'): JsonResponse
    {
        $country = Country::where('iso2', strtoupper($iso2))->firstOrFail();

        return response()->json(
            $country->states()->orderBy('name')->get(['id', 'name'])
        );
    }

    /**
     * Cities within a state.
     */
    public function cities(State $state): JsonResponse
    {
        return response()->json(
            $state->cities()->orderBy('name')->get(['id', 'name'])
        );
    }

    /**
     * Postcodes within a city.
     */
    public function postcodes(City $city): JsonResponse
    {
        return response()->json(
            $city->postcodes()->orderBy('code')->get(['id', 'code'])
        );
    }
}
