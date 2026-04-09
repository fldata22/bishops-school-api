<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Module extends Model
{
    protected $fillable = ['name', 'code', 'topics'];

    protected $casts = [
        'topics' => 'array',
    ];

    public function sessions(): HasMany
    {
        return $this->hasMany(Session::class);
    }

    public function teacherModuleAssignments(): HasMany
    {
        return $this->hasMany(TeacherModuleAssignment::class);
    }
}
