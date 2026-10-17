<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Holiday extends Model
{
    /** @use HasFactory<\Database\Factories\HolidayFactory> */
    use HasFactory;

    public const TYPE_ONE_OFF = 'one_off';
    public const TYPE_RECURRING = 'recurring';

    protected $fillable = ['project_id', 'type', 'date', 'month', 'day', 'label'];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'month' => 'integer',
            'day' => 'integer',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function matches(CarbonInterface $date): bool
    {
        if ($this->type === self::TYPE_RECURRING) {
            return $this->month !== null
                && $this->day !== null
                && (int) $date->month === $this->month
                && (int) $date->day === $this->day;
        }

        return $this->date !== null && $this->date->isSameDay($date);
    }
}
