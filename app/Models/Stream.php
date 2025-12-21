<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Stream extends Model
{
    use HasFactory;

    protected $fillable = [
        'grade_id',
        'name',
        'capacity',
    ];

    // Relationship: Stream belongs to a Grade
    public function grade() {
        return $this->belongsTo(Grade::class);
    }

    // Relationship: Stream has many students
    public function students() {
        return $this->hasMany(User::class, 'stream_id')->where('usertype', 'student');
    }
}
