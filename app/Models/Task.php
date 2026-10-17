<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Task extends Model
{
    /** @use HasFactory<\Database\Factories\TaskFactory> */
    use HasFactory;

    public const PRIORITY_LOW = 'low';
    public const PRIORITY_NORMAL = 'normal';
    public const PRIORITY_HIGH = 'high';

    public const PRIORITIES = [self::PRIORITY_LOW, self::PRIORITY_NORMAL, self::PRIORITY_HIGH];

    protected $fillable = [
        'project_id',
        'parent_id',
        'name',
        'start_date',
        'end_date',
        'duration',
        'priority',
        'color',
        'position',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'duration' => 'integer',
            'position' => 'integer',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Task::class, 'parent_id')->orderBy('position')->orderBy('id');
    }

    public function predecessors(): BelongsToMany
    {
        return $this->belongsToMany(Task::class, 'dependencies', 'successor_id', 'predecessor_id')
            ->withPivot(['id', 'type', 'delay'])
            ->withTimestamps();
    }

    public function successors(): BelongsToMany
    {
        return $this->belongsToMany(Task::class, 'dependencies', 'predecessor_id', 'successor_id')
            ->withPivot(['id', 'type', 'delay'])
            ->withTimestamps();
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(Allocation::class);
    }

    public function resources(): BelongsToMany
    {
        return $this->belongsToMany(Resource::class, 'allocations')->withTimestamps();
    }

    public function isSummary(): bool
    {
        return $this->children->isNotEmpty();
    }
}
