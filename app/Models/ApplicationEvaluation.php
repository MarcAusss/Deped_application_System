<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApplicationEvaluation extends Model
{
    protected $fillable = [
        'application_id',
        'evaluator_id',
        'resume_checked',
        'credentials_valid',
        'recommended',
        'remarks',
        'evaluated_at',
    ];

    protected $casts = [
        'resume_checked' => 'boolean',
        'credentials_valid' => 'boolean',
        'recommended' => 'boolean',
        'evaluated_at' => 'datetime',
    ];

    public function application()
    {
        return $this->belongsTo(Application::class);
    }

    public function evaluator()
    {
        return $this->belongsTo(User::class, 'evaluator_id');
    }
}
