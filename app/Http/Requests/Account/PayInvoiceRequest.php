<?php

namespace App\Http\Requests\Account;

use App\Enums\AccountKind;
use App\Models\Account;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class PayInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $tenantId = $this->user()?->tenant_id;

        return [
            'account_id' => [
                'required',
                'integer',
                Rule::exists('accounts', 'id')->where(fn ($query) => $query
                    ->where('tenant_id', $tenantId)
                    ->where('kind', AccountKind::Account->value)),
            ],
            'amount' => ['required', 'numeric', 'decimal:0,2', 'gt:0'],
            'effective_date' => ['required', 'date_format:Y-m-d'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $card = $this->route('account');
            $bankId = $this->integer('account_id');

            if ($card instanceof Account && $bankId !== 0) {
                $bank = Account::query()->find($bankId);

                if ($bank !== null && $bank->currency_id !== $card->currency_id) {
                    $validator->errors()->add('account_id', __('accounts.invoice.pay.currency_mismatch'));
                }
            }
        });
    }
}
