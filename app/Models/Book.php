<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Book extends Model
{
    protected $fillable = ['module_id', 'name', 'chapters', 'position'];
    protected $casts = ['chapters' => 'array', 'position' => 'integer'];

    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(Session::class);
    }
}
