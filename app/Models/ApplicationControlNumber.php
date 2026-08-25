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

    protected static function booted(): void
    {
        static::created(function (self $controlNumber) {
            $application = $controlNumber->application;

            if ($application && ! in_array($application->status, ['approved', 'rejected'], true)) {
                $application->update(['status' => 'evaluated']);
            }
        });
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