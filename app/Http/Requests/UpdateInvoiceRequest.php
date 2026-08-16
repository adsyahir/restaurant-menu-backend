<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $workspaceId = $this->user()->current_workspace_id;
        $invoiceId = $this->route('invoice');

        return [
            'number' => [
                'sometimes',
                'required',
                'string',
                'max:60',
                Rule::unique('invoices', 'number')
                    ->where('workspace_id', $workspaceId)
                    ->ignore($invoiceId),
            ],
            'issuedOn' => ['sometimes', 'required', 'date'],
            'amount' => ['sometimes', 'required', 'numeric', 'min:0'],
            'status' => ['sometimes', 'required', Rule::in(['paid', 'due', 'failed'])],
        ];
    }
}
