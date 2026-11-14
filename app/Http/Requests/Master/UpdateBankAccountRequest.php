<?php

namespace App\Http\Requests\Master;

use App\Models\BankAccount;
use Illuminate\Foundation\Http\FormRequest;

class UpdateBankAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdminFinance() ?? false;
    }

    public function rules(): array
    {
        return [
            'owner_name' => ['required', 'string', 'max:255'],
            'bank_id' => ['nullable', 'exists:banks,id'],
            'bank_name' => ['required', 'string', 'max:255'],
            'account_number' => ['required', 'string', 'max:50'],
            'owner_type' => ['required', 'in:' . implode(',', [
                BankAccount::TYPE_EMPLOYEE,
                BankAccount::TYPE_DRIVER,
                BankAccount::TYPE_VENDOR,
                BankAccount::TYPE_OTHER,
            ])],
            'owner_reference' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'owner_name.required' => 'Nama pemilik rekening wajib diisi.',
            'bank_name.required' => 'Nama bank wajib diisi.',
            'account_number.required' => 'Nomor rekening wajib diisi.',
            'owner_type.required' => 'Jenis pemilik rekening wajib dipilih.',
            'owner_type.in' => 'Pilihan jenis pemilik rekening tidak valid.',
        ];
    }
}

