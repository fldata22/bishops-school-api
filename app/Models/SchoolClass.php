<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SchoolClass extends Model
{
    protected $table = 'classes';
    protected $fillable = ['name', 'teacher_id', 'category'];

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class, 'class_id');
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(Session::class, 'class_id');
    }

    public function teacherModuleAssignments(): HasMany
    {
        return $this->hasMany(TeacherModuleAssignment::class, 'class_id');
    }
}
