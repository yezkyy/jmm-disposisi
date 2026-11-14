<?php

namespace App\Http\Requests\Master;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdminFinance() ?? false;
    }

    public function rules(): array
    {
        $categoryId = $this->route('category')?->id ?? $this->category?->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('categories', 'code')->ignore($categoryId),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama kategori pengeluaran wajib diisi.',
            'code.unique' => 'Kode kategori sudah digunakan oleh kategori lain.',
        ];
    }
}

