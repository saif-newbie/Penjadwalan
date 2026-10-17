<?php

namespace App\Http\Requests;

use App\Models\Project;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSettingsRequest extends FormRequest
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
        return [
            'font_size' => ['required', 'integer', 'min:10', 'max:24'],
            'date_format' => ['required', Rule::in(Project::DATE_FORMATS)],
        ];
    }
}
