<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Dependency extends Model
{
    public const TYPES = ['FS', 'SS', 'FF', 'SF'];

    /** @use HasFactory<\Database\Factories\DependencyFactory> */
    use HasFactory;

    protected $fillable = [
        'project_id',
        'predecessor_id',
        'successor_id',
        'type',
        'delay',
    ];

    protected function casts(): array
    {
        return ['delay' => 'integer'];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function predecessor(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'predecessor_id');
    }

    public function successor(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'successor_id');
    }
}
