<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAllocationRequest extends FormRequest
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
            'task_id' => [
                'required',
                Rule::exists('tasks', 'id')->where('project_id', $project->id),
            ],
            'resource_id' => [
                'required',
                Rule::exists('resources', 'id')->where('project_id', $project->id),
            ],
        ];
    }
}
