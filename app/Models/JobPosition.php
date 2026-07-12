<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JobPosition extends Model
{
    protected $fillable = [
        'title',
        'description',
        'is_open',
    ];
    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }
}
