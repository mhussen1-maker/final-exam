<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ScheduleCourse extends Model
{
    protected $fillable = [
        'student_id',
        'course_code',
        'course_name',
        'instructor',
        'room',
        'day',
        'start_time',
        'end_time',
        'section',
        'academic_year',
        'semester'
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }
}
