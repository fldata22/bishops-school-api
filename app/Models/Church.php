<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Church extends Model
{
    protected $fillable = ['name', 'denomination_id'];

    public function denomination(): BelongsTo
    {
        return $this->belongsTo(Denomination::class);
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }
}
