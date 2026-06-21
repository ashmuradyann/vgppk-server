<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PracticeBase extends Model
{
    protected $fillable = ['organisation', "supervisors"];

    public function students()
    {
        return $this->hasMany(Student::class);
    }
}
