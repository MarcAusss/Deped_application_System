<?php

namespace App\Models;

 
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
     
    use HasFactory, Notifiable;

    




    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'is_approved',
    ];



    




    protected $hidden = [
        'password',
        'remember_token',
    ];

    




    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function isAdmin()
    {
        return $this->role === 'admin';
    }

    public function isEvaluator()
    {
        return $this->role === 'evaluator';
    }

    public function canAccessPanel(\Filament\Panel $panel): bool
    {
        if ($panel->getId() === 'admin') {
            return $this->role === 'admin';
        }

        if ($panel->getId() === 'evaluator') {
            return $this->role === 'evaluator' && $this->is_approved;
        }

        return false;
    }
    
}
