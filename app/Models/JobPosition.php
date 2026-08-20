<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JobPosition extends Model
{
    protected $fillable = [
        'title',
        'description',
        'salary_grade',
        'monthly_salary',
        'education_requirement',
        'training_requirement',
        'experience_requirement',
        'eligibility_requirement',
        'is_open',
        'posted_at',
        'until',
    ];

    protected $casts = [
        'monthly_salary' => 'decimal:2',
        'is_open' => 'boolean',
        'posted_at' => 'date',
        'until' => 'date',
    ];

    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }
}
