<?php
namespace App\Models;


use Illuminate\Database\Eloquent\Model;
class ApplicantEligibility extends Model
{
    protected $fillable = [
        'application_id',
        'license_name',
        'rating',
        'valid_until',
    ];

    public function application()
    {
        return $this->belongsTo(Application::class);
    }
}