<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JobPosition extends Model
{
    protected $fillable = [
        'jp_number',
        'title',
        'abbreviation',
        'slots',
        'description',
        'salary_grade',
        'monthly_salary',
        'education_requirement',
        'training_requirement',
        'min_training_hours',
        'experience_requirement',
        'min_experience_years',
        'eligibility_requirement',
        'is_open',
        'posted_at',
        'until',
        'until_time',
        'attachment_paths',
        'csc_publication_path',
    ];

    protected $casts = [
        'slots' => 'integer',
        'monthly_salary' => 'decimal:2',
        'is_open' => 'boolean',
        'posted_at' => 'date',
        'until' => 'date',
        'min_experience_years' => 'decimal:2',
        'min_training_hours' => 'integer',
        'attachment_paths' => 'array',
    ];

    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }

    public function hasDeadlinePassed(): bool
    {
        if (blank($this->until)) {
            return false;
        }

        $deadline = \Carbon\Carbon::parse(
            $this->until->toDateString() . ' ' . ($this->until_time ?? '23:59:59')
        );

        return now()->greaterThan($deadline);
    }

    public function scopeCurrentlyOpen($query)
    {
        return $query->where('is_open', true)
            ->where(function ($q) {
                $q->whereNull('until')
                    ->orWhereRaw("TIMESTAMP(until, COALESCE(until_time, '23:59:59')) >= ?", [now()]);
            });
    }

    public function scopeClosedOrExpired($query)
    {
        return $query->where(function ($q) {
            $q->where('is_open', false)
                ->orWhere(function ($q2) {
                    $q2->whereNotNull('until')
                        ->whereRaw("TIMESTAMP(until, COALESCE(until_time, '23:59:59')) < ?", [now()]);
                });
        });
    }

    /**
     * Generate a unique JP Number in the format JP-0001, assigned only
     * the first time a position is posted.
     */
    public static function generateJpNumber(): string
    {
        $next = self::whereNotNull('jp_number')->count() + 1;

        do {
            $candidate = 'JP-' . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
            $next++;
        } while (self::where('jp_number', $candidate)->exists());

        return $candidate;
    }
}
