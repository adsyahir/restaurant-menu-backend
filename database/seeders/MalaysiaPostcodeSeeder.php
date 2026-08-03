<?php

namespace Database\Seeders;

use App\Models\City;
use App\Models\Country;
use App\Models\Postcode;
use App\Models\State;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MalaysiaPostcodeSeeder extends Seeder
{
    /**
     * Seed Malaysian states, cities and postcodes from the bundled JSON file.
     */
    public function run(): void
    {
        $path = database_path('data/malaysia_postcodes.json');

        if (! is_file($path)) {
            $this->command?->warn("Missing data file: {$path} — skipping.");

            return;
        }

        /** @var array{state: array<int, array{name: string, city: array<int, array{name: string, postcode: array<int, string>}>}>} $data */
        $data = json_decode((string) file_get_contents($path), true);

        $now = now();

        DB::transaction(function () use ($data, $now) {
            $country = Country::firstOrCreate(['iso2' => 'MY'], ['name' => 'Malaysia']);

            foreach ($data['state'] as $stateData) {
                $state = State::firstOrCreate([
                    'country_id' => $country->id,
                    'name' => $stateData['name'],
                ]);

                foreach ($stateData['city'] as $cityData) {
                    $city = City::create([
                        'state_id' => $state->id,
                        'name' => $cityData['name'],
                    ]);

                    $rows = array_map(fn (string $code): array => [
                        'city_id' => $city->id,
                        'code' => $code,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ], $cityData['postcode']);

                    foreach (array_chunk($rows, 500) as $chunk) {
                        Postcode::insert($chunk);
                    }
                }
            }
        });

        $this->command?->info('Seeded '.Country::count().' country, '.State::count().' states, '.City::count().' cities, '.Postcode::count().' postcodes.');
    }
}
