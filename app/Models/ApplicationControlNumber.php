<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApplicationControlNumber extends Model
{
    protected $fillable = [
        'application_id',
        'control_number',
        'generated_by',
    ];

    /**
     * Generate a unique control number in the format
     * Alb-{Job Position Title}-{4 random unique digits}-{current year}.
     */
    public static function generateFor(JobPosition $jobPosition): string
    {
        do {
            $candidate = 'Alb-' . $jobPosition->title . '-' . random_int(1000, 9999) . '-' . now()->year;
        } while (self::where('control_number', $candidate)->exists());

        return $candidate;
    }

    public function application()
    {
        return $this->belongsTo(Application::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'generated_by');
    }
}