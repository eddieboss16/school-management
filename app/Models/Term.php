<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Term extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'start_date',
        'end_date',
        'is_active',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
    ];

    public function grades()
    {
        return $this->hasMany(StudentGrade::class);
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    public function feeStructures()
    {
        return $this->hasMany(FeeStructure::class);
    }

    public function feePayments()
    {
        return $this->hasMany(FeePayment::class);
    }

    public static function activeTerm()
    {
        return static::where('is_active', true)->first();
    }
}
