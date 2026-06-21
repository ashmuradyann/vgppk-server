<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    protected $fillable = ['full_name', "inner_supervisor", "student_group_id", "practice_base_id", "practice_supervisor"];

    public function practiceBase()
    {
        return $this->belongsTo(PracticeBase::class);
    }
}
