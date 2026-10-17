<?php

namespace App\Http\Requests;

use App\Models\Holiday;
use App\Models\Project;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class WizardStep3Request extends FormRequest
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
            'weekend_mode' => ['required', Rule::in([Project::WEEKEND_OFF, Project::WEEKEND_CONTINUOUS])],
            'holiday_mode' => ['required', Rule::in([Project::HOLIDAY_NONE, Project::HOLIDAY_CUSTOM])],
            'holidays' => ['array', 'max:100'],
            'holidays.*.type' => ['required', Rule::in([Holiday::TYPE_ONE_OFF, Holiday::TYPE_RECURRING])],
            'holidays.*.date' => ['nullable', 'date'],
            'holidays.*.month' => ['nullable', 'integer', 'min:1', 'max:12'],
            'holidays.*.day' => ['nullable', 'integer', 'min:1', 'max:31'],
            'holidays.*.label' => ['required', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'holidays.*.date.date' => 'Format tanggal libur tidak valid.',
            'holidays.*.month.between' => 'Bulan recurring harus antara 1-12.',
            'holidays.*.day.between' => 'Hari recurring harus antara 1-31.',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            foreach ((array) $this->input('holidays', []) as $i => $holiday) {
                $type = $holiday['type'] ?? null;

                if ($type === Holiday::TYPE_ONE_OFF && empty($holiday['date'])) {
                    $validator->errors()->add("holidays.$i.date", 'Tanggal wajib diisi untuk libur satu kali.');
                }

                if ($type === Holiday::TYPE_RECURRING
                    && (empty($holiday['month']) || empty($holiday['day']))) {
                    $validator->errors()->add("holidays.$i.month", 'Bulan dan hari wajib diisi untuk libur berulang.');
                }
            }
        });
    }
}
