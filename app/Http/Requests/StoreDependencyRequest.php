<?php

namespace App\Http\Requests;

use App\Models\Dependency;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDependencyRequest extends FormRequest
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
            'predecessor_id' => [
                'required',
                Rule::exists('tasks', 'id')->where('project_id', $project->id),
            ],
            'successor_id' => [
                'required',
                Rule::exists('tasks', 'id')->where('project_id', $project->id),
                Rule::notIn([$this->input('predecessor_id')]),
            ],
            'type' => ['required', Rule::in(Dependency::TYPES)],
            'delay' => ['nullable', 'integer', 'min:0', 'max:365'],
        ];
    }
}
