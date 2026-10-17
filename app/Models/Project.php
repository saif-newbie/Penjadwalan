<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    /** @use HasFactory<\Database\Factories\ProjectFactory> */
    use HasFactory;

    public const WEEKEND_OFF = 'off';
    public const WEEKEND_CONTINUOUS = 'continuous';

    public const HOLIDAY_NONE = 'none';
    public const HOLIDAY_CUSTOM = 'custom';

    public const DATE_FORMATS = ['mm/dd/yyyy', 'dd/mm/yyyy'];

    protected $fillable = [
        'name',
        'organization',
        'website',
        'description',
        'start_date',
        'weekend_mode',
        'holiday_mode',
        'date_format',
        'font_size',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'font_size' => 'integer',
        ];
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function rootTasks(): HasMany
    {
        return $this->hasMany(Task::class)->whereNull('parent_id')->orderBy('position')->orderBy('id');
    }

    public function dependencies(): HasMany
    {
        return $this->hasMany(Dependency::class);
    }

    public function resources(): HasMany
    {
        return $this->hasMany(Resource::class);
    }

    public function holidays(): HasMany
    {
        return $this->hasMany(Holiday::class);
    }

    public function roles(): HasMany
    {
        return $this->hasMany(Role::class)->orderBy('sort_order');
    }

    public function allocations(): BelongsToMany
    {
        return $this->belongsToMany(Resource::class, 'allocations')
            ->withPivot('task_id');
    }
}
