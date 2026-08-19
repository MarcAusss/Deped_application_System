<?php
namespace App\Models;


use Illuminate\Database\Eloquent\Model;
class ApplicantTraining extends Model
{
    protected $fillable = [
        'application_id',
        'title',
        'hours',
        'training_date',
        'training_end_date',
        'details',
    ];

    protected $casts = [
        'training_date' => 'date',
        'training_end_date' => 'date',
    ];

    public function application()
    {
        return $this->belongsTo(Application::class);
    }
}
