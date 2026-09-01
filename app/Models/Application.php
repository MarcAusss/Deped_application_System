<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Application extends Model
{
    protected $fillable = [
        'job_position_id',
        'applicant_id',
        'status',
    ];

    public function applicant(): BelongsTo
    {
        return $this->belongsTo(Applicant::class);
    }







    public function jobPosition(): BelongsTo
    {
        return $this->belongsTo(JobPosition::class);
    }

    public function profile()
    {
        return $this->hasOne(ApplicantProfile::class);
    }

    public function educations()
    {
        return $this->hasMany(ApplicantEducation::class);
    }

    public function experiences()
    {
        return $this->hasMany(ApplicantExperience::class);
    }

    public function trainings()
    {
        return $this->hasMany(ApplicantTraining::class);
    }

    public function eligibilities()
    {
        return $this->hasMany(ApplicantEligibility::class);
    }

    public function documents()
    {
        return $this->hasMany(ApplicationDocument::class);
    }

    public function controlNumber()
    {
        return $this->hasOne(ApplicationControlNumber::class);
    }

    public function logs()
    {
        return $this->hasMany(ApplicationStatusLog::class);
    }
    public function evaluation()
    {
        return $this->hasOne(ApplicationEvaluation::class);
    }
}
