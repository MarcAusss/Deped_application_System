<?php
namespace App\Models;


use Illuminate\Database\Eloquent\Model;

class ApplicantProfile extends Model
{
    protected $fillable = [
        'application_id',
        'full_name',
        'email',
        'phone',
        'address',
        'birth_date',
        'sex',
        'civil_status',
        'religion',
        'disability',
        'ethnic_group',
    ];

    protected $casts = [
        'birth_date' => 'date',
    ];

    public function application()
    {
        return $this->belongsTo(Application::class);
    }
}
