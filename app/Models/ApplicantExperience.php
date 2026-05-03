<?php
namespace App\Models;


use Illuminate\Database\Eloquent\Model;
class ApplicantExperience extends Model
{
    protected $fillable = [
        'application_id',
        'title',
        'company',
        'years_months',
        'details',
    ];

    public function application()
    {
        return $this->belongsTo(Application::class);
    }
}