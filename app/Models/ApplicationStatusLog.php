<?php
namespace App\Models;


use Illuminate\Database\Eloquent\Model;
class ApplicationStatusLog extends Model
{
    protected $fillable = [
        'application_id',
        'status',
        'remarks',
        'changed_by',
    ];

    public function application()
    {
        return $this->belongsTo(Application::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}