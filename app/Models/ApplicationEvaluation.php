<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApplicationEvaluation extends Model
{
    public const RESULT_PENDING_DOCUMENT_REVIEW = 'pending_document_review';
    public const RESULT_QUALIFIED = 'qualified';
    public const RESULT_EXCLUDED = 'excluded';

    protected $fillable = [
        'application_id',
        'evaluator_id',
        'resume_checked',
        'credentials_valid',
        'recommended',
        'remarks',
        'evaluated_at',
        'documentary_mandatory',
        'documentary_other',
        'qs_education_met',
        'qs_experience_met',
        'qs_training_met',
        'qs_eligibility_met',
        'result',
    ];

    protected $casts = [
        'resume_checked' => 'boolean',
        'credentials_valid' => 'boolean',
        'recommended' => 'boolean',
        'evaluated_at' => 'datetime',
        'documentary_mandatory' => 'array',
        'documentary_other' => 'array',
        'qs_education_met' => 'boolean',
        'qs_experience_met' => 'boolean',
        'qs_training_met' => 'boolean',
        'qs_eligibility_met' => 'boolean',
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
