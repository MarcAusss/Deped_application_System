<?php
namespace App\Models;


use Illuminate\Database\Eloquent\Model;
class ApplicantTraining extends Model
{
    protected $fillable = [
        'application_id',
        'title',
        'hours',
        'details',
    ];

    public function application()
    {
        return $this->belongsTo(Application::class);
    }
}