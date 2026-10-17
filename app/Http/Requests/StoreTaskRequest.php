<?php

namespace App\Http\Requests;

use App\Models\Task;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $project = $this->route('project');

        return [
            'name' => ['required', 'string', 'max:255'],
            'start_date' => ['required', 'date'],
            'duration' => ['required', 'integer', 'min:1', 'max:1000'],
            'priority' => ['required', Rule::in(Task::PRIORITIES)],
            'color' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'parent_id' => [
                'nullable',
                Rule::exists('tasks', 'id')->where('project_id', $project->id),
            ],
        ];
    }
}
