<?php

namespace App\Http\Requests\Disposition;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDispositionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $disposition = $this->route('disposition');
        return $disposition && $disposition->canBeEditedBy($this->user());
    }

    public function rules(): array
    {
        return [
            'disposition_date' => ['required', 'date'],
            'subject' => ['required', 'string', 'max:255'],
            'invoice_number' => ['nullable', 'string', 'max:100'],
            'division_id' => ['nullable', 'exists:divisions,id'],
            'description' => ['nullable', 'string'],
            'ppn' => ['nullable'],
            'action' => ['required', 'in:draft,submit'],

            // Rincian Pengeluaran (Multi-item: Uraian, Qty, Satuan, Subtotal, Nota)
            'items' => ['required', 'array', 'min:1'],
            'items.*.category_id' => ['nullable'],
            'items.*.item_name' => ['nullable', 'string', 'max:255'],
            'items.*.description' => ['nullable', 'string'],
            'items.*.invoice_number' => ['nullable', 'string', 'max:100'],
            'items.*.qty' => ['nullable'],
            'items.*.quantity' => ['nullable'],
            'items.*.unit' => ['nullable', 'string', 'max:50'],
            'items.*.unit_price' => ['nullable'],
            'items.*.amount' => ['required'],
            'items.*.subtotal' => ['nullable'],
            'items.*.receipt' => ['nullable', 'file', 'max:10240', 'mimes:pdf,jpg,jpeg,png'],
            'items.*.existing_receipt_path' => ['nullable', 'string'],
            'items.*.existing_receipt_name' => ['nullable', 'string'],
            'items.*.notes' => ['nullable', 'string'],

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
            'recipients.*.notes' => ['nullable', 'string', 'max:500'],

            'attachments' => ['nullable', 'array'],
            'attachments.*' => ['file', 'max:10240', 'mimes:pdf,jpg,jpeg,png'],
        ];
    }
}
