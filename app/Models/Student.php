<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    protected $fillable = ['student_id', 'name', 'token'];

    public function courses()
    {
        return $this->hasMany(ScheduleCourse::class);
    }
}
