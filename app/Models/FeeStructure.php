<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FeeStructure extends Model
{
    use HasFactory;

    protected $fillable = [
        'term_id',
        'grade_id',
        'name',
        'amount',
        'description',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function term()
    {
        return $this->belongsTo(Term::class);
    }

    public function grade()
    {
        return $this->belongsTo(Grade::class);
    }

    /**
     * Get all fee structures that apply to a student for a given term.
     * Includes global fees (grade_id = null) + grade-specific fees.
     */
    public static function forStudent(User $student, int $termId)
    {
        $gradeId = $student->stream?->grade_id;

        return static::where('term_id', $termId)
            ->where(function ($q) use ($gradeId) {
                $q->whereNull('grade_id');
                if ($gradeId) {
                    $q->orWhere('grade_id', $gradeId);
                }
            })
            ->get();
    }
}
