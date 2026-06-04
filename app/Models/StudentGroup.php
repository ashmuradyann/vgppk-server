<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StudentGroup extends Model
{
    // Разрешаем массовое заполнение полей
    protected $fillable = ["id", 'name', "course", 'teacher_name', 'specialty_id'];

    // СВЯЗЬ: Группа принадлежит специальности
    public function specialty(): BelongsTo
    {
        return $this->belongsTo(Specialty::class);
    }

    // СВЯЗЬ: У группы много студентов
    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }

    public function practices(): HasMany
    {
        return $this->hasMany(Practice::class);
    }
}

?>