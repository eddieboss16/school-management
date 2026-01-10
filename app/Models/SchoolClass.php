<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SchoolClass extends Model
{
    use HasFactory;

    protected $table = 'classes'; // Tell Laravel the table name

    protected $fillable = [
        'grade_id',
        'stream_id',
        'subject_id',
        'teacher_id',
        'class_code',
        'capacity',
    ];

    // Relationships
    public function grade()
    {
        return $this->belongsTo(Grade::class);
    }

    public function stream()
    {
        return $this->belongsTo(Stream::class);
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    // Students enrolled in this class (we'll create this pivot table next)
    public function students()
    {
        return $this->belongsToMany(User::class, 'enrollments', 'class_id', 'student_id');
    }
}
