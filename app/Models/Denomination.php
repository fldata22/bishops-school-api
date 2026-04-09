<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Denomination extends Model
{
    protected $fillable = ['name', 'abbreviation'];

    public function churches(): HasMany
    {
        return $this->hasMany(Church::class);
    }
}
