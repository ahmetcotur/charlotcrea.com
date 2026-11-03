<?php

namespace App\Http\Requests\Twill;

use A17\Twill\Http\Requests\Admin\Request;
use App\Models\Quote;
use Illuminate\Validation\Rule;

class QuoteRequest extends Request
{
    public function rulesForCreate(): array
    {
        return $this->quoteRules();
    }

    public function rulesForUpdate(): array
    {
        return $this->quoteRules();
    }

    private function quoteRules(): array
    {
        $id = $this->route('quote') ?? $this->route('id');

        return [
            'title' => ['required', 'string', 'max:200'],
            'quote_number' => ['nullable', 'string', 'max:40', Rule::unique('quotes', 'quote_number')->ignore($id)],
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'status' => ['required', Rule::in(Quote::STATUSES)],
            'amount' => ['required', 'numeric', 'min:0', 'max:9999999999.99'],
            'currency' => ['required', Rule::in(['EUR', 'USD', 'TRY'])],
            'valid_until' => ['nullable', 'date'],
            'scope' => ['nullable', 'string', 'max:20000'],
            'internal_notes' => ['nullable', 'string', 'max:20000'],
        ];
    }
}
