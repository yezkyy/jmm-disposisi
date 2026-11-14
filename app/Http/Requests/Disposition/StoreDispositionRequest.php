<?php

namespace App\Http\Requests\Disposition;

use Illuminate\Foundation\Http\FormRequest;

class StoreDispositionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'disposition_date' => ['required', 'date'],
            'subject' => ['required', 'string', 'max:255'],
            'division_id' => ['nullable', 'exists:divisions,id'],
            'description' => ['nullable', 'string'],
            'action' => ['required', 'in:draft,submit'],

            // Rincian Pengeluaran (Multi-item)
            'items' => ['required', 'array', 'min:1'],
            'items.*.category_id' => ['nullable', 'exists:categories,id'],
            'items.*.item_name' => ['nullable', 'string', 'max:255'],
            'items.*.description' => ['nullable', 'string'],
            'items.*.quantity' => ['nullable'],
            'items.*.unit' => ['nullable', 'string'],
            'items.*.unit_price' => ['nullable'],
            'items.*.amount' => ['required'],

            // Penerima (Multi-recipient)
            'recipients' => ['required', 'array', 'min:1'],
            'recipients.*.recipient_name' => ['required', 'string', 'max:255'],
            'recipients.*.recipient_type' => ['nullable', 'in:employee,driver,vendor,other'],
            'recipients.*.primary_bank_account_id' => ['nullable', 'exists:bank_accounts,id'],
            'recipients.*.primary_account_name' => ['nullable', 'string', 'max:255'],
            'recipients.*.primary_account_holder' => ['nullable', 'string', 'max:255'],
            'recipients.*.primary_bank_name' => ['nullable', 'string', 'max:255'],
            'recipients.*.primary_account_number' => ['nullable', 'string', 'max:50'],
            'recipients.*.secondary_bank_account_id' => ['nullable', 'exists:bank_accounts,id'],
            'recipients.*.secondary_account_name' => ['nullable', 'string', 'max:255'],
            'recipients.*.secondary_account_holder' => ['nullable', 'string', 'max:255'],
            'recipients.*.secondary_bank_name' => ['nullable', 'string', 'max:255'],
            'recipients.*.secondary_account_number' => ['nullable', 'string', 'max:50'],
            'recipients.*.allocated_amount' => ['nullable'],
            'recipients.*.amount' => ['nullable'],

            // Lampiran
            'attachments' => ['nullable', 'array'],
            'attachments.*' => ['file', 'max:10240', 'mimes:pdf,jpg,jpeg,png'],
        ];
    }

    public function messages(): array
    {
        return [
            'disposition_date.required' => 'Tanggal disposisi wajib diisi.',
            'subject.required' => 'Perihal pengajuan disposisi wajib diisi.',
            'items.required' => 'Minimal satu rincian pengeluaran harus dicantumkan.',
            'items.min' => 'Minimal satu rincian pengeluaran harus dicantumkan.',
            'items.*.category_id.required' => 'Kategori pengeluaran pada setiap rincian wajib dipilih.',
            'items.*.description.required' => 'Uraian pengeluaran wajib diisi.',
            'items.*.amount.required' => 'Nominal pengeluaran wajib diisi.',
            'recipients.required' => 'Minimal satu data penerima harus dicantumkan.',
            'recipients.min' => 'Minimal satu data penerima harus dicantumkan.',
            'recipients.*.recipient_name.required' => 'Nama penerima wajib diisi.',
            'attachments.*.max' => 'Ukuran file lampiran maksimal 10 MB.',
            'attachments.*.mimes' => 'Format file lampiran yang diperbolehkan hanya PDF, JPG, JPEG, dan PNG.',
        ];
    }
}
