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

    public function application()
    {
        return $this->belongsTo(Application::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'generated_by');
    }
}