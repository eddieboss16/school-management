<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'usertype',
        'stream_id',
        'admission_number',
        'parent_id',
    ];

    public function parent() {
        return $this->belongsTo(User::class, 'parent_id');
    }

    public function children() {
        return $this->hasMany(User::class, 'parent_id');
    }

    public function stream() {
        return $this->belongsTo(Stream::class);
    }

    // Relationship: User enrolled classes (for students)
    public function enrolledClasses() {
        return $this->belongsToMany(SchoolClass::class, 'enrollments', 'student_id', 'class_id');
    }

    // Relationship: User teaching classes (for teachers)
    public function teachingClasses() {
        return $this->hasMany(SchoolClass::class, 'teacher_id');
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
