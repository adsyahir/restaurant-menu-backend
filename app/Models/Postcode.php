<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Postcode extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = ['city_id', 'code'];

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }
}
