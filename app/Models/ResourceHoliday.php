<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResourceHoliday extends Model
{
    /** @use HasFactory<\Database\Factories\ResourceHolidayFactory> */
    use HasFactory;

    protected $fillable = ['resource_id', 'date', 'label'];

    protected function casts(): array
    {
        return ['date' => 'date'];
    }

    public function resource(): BelongsTo
    {
        return $this->belongsTo(Resource::class);
    }
}
