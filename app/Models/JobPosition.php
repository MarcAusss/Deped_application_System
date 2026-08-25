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
        'min_training_hours',
        'experience_requirement',
        'min_experience_years',
        'eligibility_requirement',
        'is_open',
        'posted_at',
        'until',
        'until_time',
        'attachment_path',
        'csc_publication_path',
    ];

    protected $casts = [
        'monthly_salary' => 'decimal:2',
        'is_open' => 'boolean',
        'posted_at' => 'date',
        'until' => 'date',
        'min_experience_years' => 'decimal:2',
        'min_training_hours' => 'integer',
    ];

    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }
}
