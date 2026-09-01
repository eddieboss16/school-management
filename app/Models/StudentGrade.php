<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StudentGrade extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'student_grades';

    protected $fillable = [
        'class_id',
        'student_id',
        'term_id',
        'assessment_type',
        'score',
        'max_score',
        'assessment_date',
        'remarks',
        'entered_by',
    ];

    protected $casts = [
        'assessment_date' => 'date',
        'score' => 'decimal:2',
        'max_score' => 'decimal:2',
    ];

    // Relationships
    public function class()
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function enteredBy()
    {
        return $this->belongsTo(User::class, 'entered_by');
    }

    public function term()
    {
        return $this->belongsTo(Term::class);
    }

    // Helper method to get percentage
    public function getPercentageAttribute()
    {
        return $this->max_score > 0 ? round(($this->score / $this->max_score) * 100, 2) : 0;
    }
}
