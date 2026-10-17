<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Role extends Model
{
    /** @use HasFactory<\Database\Factories\RoleFactory> */
    use HasFactory;

    public const TEMPLATE_DEFAULT = 'default';
    public const TEMPLATE_SOFTWARE = 'software_development';

    /** @var array<string, list<string>> */
    public const TEMPLATES = [
        self::TEMPLATE_DEFAULT => [
            'Project Manager',
            'Business Analyst',
            'Developer',
            'QA Engineer',
            'UI Designer',
        ],
        self::TEMPLATE_SOFTWARE => [
            'Project Manager',
            'Analis',
            'Programmer',
            'Tester',
            'UI/UX Designer',
            'DevOps Engineer',
        ],
    ];

    protected $fillable = ['project_id', 'name', 'sort_order'];

    protected function casts(): array
    {
        return ['sort_order' => 'integer'];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
