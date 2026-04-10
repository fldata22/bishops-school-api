<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Module extends Model
{
    protected $fillable = ['name', 'code'];

    public function sessions(): HasMany
    {
        return $this->hasMany(Session::class);
    }

    public function books(): HasMany
    {
        return $this->hasMany(Book::class)->orderBy('position');
    }

    public function teacherModuleAssignments(): HasMany
    {
        return $this->hasMany(TeacherModuleAssignment::class);
    }
}
