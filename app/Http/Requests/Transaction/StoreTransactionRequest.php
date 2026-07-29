<?php

namespace App\Http\Requests\Transaction;

use App\Enums\TransactionInstallmentFrequency;
use App\Enums\TransactionMovementType;
use App\Enums\TransactionType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTransactionRequest extends FormRequest
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
        $currencyId = $this->integer('currency_id');
        $tenantId = $this->user()?->tenant_id;
        $isTransfer = $this->string('movement_type')->toString() === TransactionMovementType::Transfer->value;
        $isInstallment = $this->string('type')->toString() === TransactionType::Installment->value;

        return [
            'movement_type' => ['required', Rule::enum(TransactionMovementType::class)],
            'type' => ['required', Rule::enum(TransactionType::class)],
            // A transfer already says what it is — the list shows "origin → destination"
            // under it — so naming it is optional and defaults to "Transfer".
            'description' => [Rule::requiredIf(! $isTransfer), 'nullable', 'string', 'max:255'],
            // Free-form and never required: the room the description does not
            // have, for an order number, a contract, whatever else is worth
            // keeping. The form no longer offers `observations`, but the rule
            // stays so the entries that already carry one still validate.
            'note' => ['nullable', 'string', 'max:2000'],
            'observations' => ['nullable', 'string', 'max:2000'],
            'currency_id' => ['required', 'integer', 'exists:currencies,id'],
            'account_id' => [
                'integer',
                'required',
                Rule::exists('accounts', 'id')->where(fn ($query) => $query
                    ->where('currency_id', $currencyId)
                    ->where('tenant_id', $tenantId)),
            ],
            'destination_account_id' => [
                Rule::requiredIf($isTransfer),
                'nullable',
                'integer',
                'different:account_id',
                Rule::exists('accounts', 'id')->where(fn ($query) => $query
                    ->where('currency_id', $currencyId)
                    ->where('tenant_id', $tenantId)),
            ],
            'category_id' => [
                'nullable',
                'integer',
                Rule::exists('categories', 'id')->where(fn ($query) => $query->where('tenant_id', $tenantId)),
            ],
            // Who the money came from or went to: the client on an income, the
            // provider on an expense. A transfer moves between the user's own
            // accounts, so there is nobody on the other side of it.
            'contact_id' => [
                'nullable',
                'integer',
                Rule::exists('contacts', 'id')->where(fn ($query) => $query->where('tenant_id', $tenantId)),
            ],
            // What the entry was made of. The amount of the transaction is taken
            // from these when they are present, so each line has to stand on its
            // own as money. A line with no service is one left behind by a service
            // that was retired: it keeps its amount so the total still adds up.
            'services' => ['nullable', 'array', 'max:50'],
            'services.*.service_id' => [
                'nullable',
                'integer',
                Rule::exists('services', 'id')->where(fn ($query) => $query->where('tenant_id', $tenantId)),
            ],
            'services.*.amount' => ['required', 'numeric', 'decimal:0,2', 'gt:0', 'max:9999999999999999.99'],
            'tags' => ['nullable', 'array'],
            'tags.*' => [
                'integer',
                Rule::exists('tags', 'id')->where(fn ($query) => $query->where('tenant_id', $tenantId)),
            ],
            'effective_date' => ['required', 'date_format:Y-m-d'],
            // Settled on the spot or still to come. A transfer is always settled,
            // so the form hides the choice there.
            'paid' => ['nullable', 'boolean'],
            'amount' => ['required', 'numeric', 'decimal:0,2', 'gt:0', 'max:9999999999999999.99'],
            'adjustment_amount' => ['nullable', 'numeric', 'decimal:0,2', 'gte:0'],
            'adjustment_month' => ['nullable', 'date_format:Y-m-d'],
            'interest_amount' => ['nullable', 'numeric', 'decimal:0,2', 'gte:0'],
            'installment_frequency' => [
                Rule::requiredIf($isInstallment),
                'nullable',
                Rule::enum(TransactionInstallmentFrequency::class),
            ],
            'installments_total' => [
                Rule::requiredIf($isInstallment),
                'nullable',
                'integer',
                'min:1',
                'max:600',
            ],
            'installment_number' => ['nullable', 'integer', 'min:1'],
            'attachment' => ['nullable', 'file', 'max:10240', 'mimes:pdf,jpg,jpeg,png,webp,doc,docx,xls,xlsx'],
            'effective_until' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:effective_date'],
        ];
    }
}
