<?php

namespace App\Http\Requests\Master;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdminFinance() ?? false;
    }

    public function rules(): array
    {
        $userId = $this->route('user')?->id ?? $this->user?->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($userId),
            ],
            'role_id' => ['required', 'exists:roles,id'],
            'division_id' => ['nullable', 'exists:divisions,id'],
            'is_active' => ['boolean'],
            'password' => ['nullable', 'string', 'min:8'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama lengkap pengguna wajib diisi.',
            'email.required' => 'Alamat email wajib diisi.',
            'email.unique' => 'Alamat email sudah digunakan oleh pengguna lain.',
            'role_id.required' => 'Peran (role) pengguna wajib dipilih.',
            'role_id.exists' => 'Peran yang dipilih tidak valid.',
            'division_id.exists' => 'Divisi yang dipilih tidak valid.',
            'password.min' => 'Password minimal harus 8 karakter bila diubah.',
        ];
    }
}

