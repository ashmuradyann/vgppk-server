<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PracticeBase extends Model
{
    protected $fillable = ['organisation', "supervisors",  "address"];
}
