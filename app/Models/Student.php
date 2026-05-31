<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    protected $fillable = ['full_name', "student_group_id", "practice_base_id", "practice_supervisor"];
}
