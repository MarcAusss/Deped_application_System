<?php
namespace App\Models;


use Illuminate\Database\Eloquent\Model;
class ApplicantEligibility extends Model
{
    protected $fillable = [
        'application_id',
        'license_name',
        'license_specify',
        'rating',
        'date_issued',
        'valid_until',
        'never_expires',
    ];

    protected $casts = [
        'never_expires' => 'boolean',
    ];

    public function application()
    {
        return $this->belongsTo(Application::class);
    }
}