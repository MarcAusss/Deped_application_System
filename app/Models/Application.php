<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Application extends Model
{
    protected $fillable = [
        'job_position_id',
        'full_name',
        'email',
        'phone_number',
        'resume',
        'status',
        'evaluated_by_evaluator',
        'final_reviewed_by_admin',
    ];

    public function jobPosition()
    {
        return $this->belongsTo(JobPosition::class);
    }
}