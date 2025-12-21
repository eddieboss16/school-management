<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Grade extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'level',
        'order',
    ];

    // Relationship: Grade has many streams
    public function streams() {
        return $this->hasMany(Stream::class);
    }
}
