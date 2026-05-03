<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApplicationDocument extends Model
{
    protected $fillable = [
        'application_id',
        'type',
        'file_path',
    ];

    public function application()
    {
        return $this->belongsTo(Application::class);
    }
}