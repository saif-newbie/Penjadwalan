<?php

namespace App\Http\Requests;

use App\Models\Holiday;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreHolidayRequest extends FormRequest
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
            'type' => ['required', Rule::in([Holiday::TYPE_ONE_OFF, Holiday::TYPE_RECURRING])],
            'date' => ['nullable', 'date'],
            'month' => ['nullable', 'integer', 'min:1', 'max:12'],
            'day' => ['nullable', 'integer', 'min:1', 'max:31'],
            'label' => ['required', 'string', 'max:255'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $type = $this->input('type');

            if ($type === Holiday::TYPE_ONE_OFF && empty($this->input('date'))) {
                $validator->errors()->add('date', 'Tanggal wajib diisi untuk libur satu kali.');
            }

            if ($type === Holiday::TYPE_RECURRING
                && (empty($this->input('month')) || empty($this->input('day')))) {
                $validator->errors()->add('month', 'Bulan dan hari wajib diisi untuk libur berulang.');
            }
        });
    }
}
