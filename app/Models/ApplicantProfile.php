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
        'disability',
        'ethnic_group',
    ];

    public function application()
    {
        return $this->belongsTo(Application::class);
    }
}