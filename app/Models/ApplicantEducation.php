<?php
namespace App\Models;


use Illuminate\Database\Eloquent\Model;
class ApplicantEducation extends Model
{
    protected $table = 'applicant_educations'; 
    
    protected $fillable = [
        'application_id',
        'level',
        'level_specify',
        'school',
        'degree',
        'year_graduated',
    ];

    public function application()
    {
        return $this->belongsTo(Application::class);
    }
}