<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Practice extends Model
{
    protected $fillable = ['name', 'start_date', 'end_date', 'type', 'student_group_id'];

    public function studentGroup(): BelongsTo
    {
        return $this->belongsTo(StudentGroup::class);
    }
}