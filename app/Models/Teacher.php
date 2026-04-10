<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Teacher extends Model
{
    protected $fillable = ['name'];

    public function classes(): HasMany
    {
        return $this->hasMany(SchoolClass::class);
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(Session::class);
    }

    public function teacherModuleAssignments(): HasMany
    {
        return $this->hasMany(TeacherModuleAssignment::class);
    }
}
