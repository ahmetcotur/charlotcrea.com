<?php

namespace App\Http\Requests\Twill;

use A17\Twill\Http\Requests\Admin\Request;
use App\Models\Work;
use Illuminate\Validation\Rule;

class WorkRequest extends Request
{
    public function rulesForCreate(): array
    {
        return $this->rules();
    }

    public function rulesForUpdate(): array
    {
        return $this->rules();
    }

    private function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:200'],
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'quote_id' => ['nullable', 'integer', 'exists:quotes,id'],
            'status' => ['required', Rule::in(Work::STATUSES)],
            'starts_at' => ['nullable', 'date'],
            'due_at' => ['nullable', 'date'],
            'brief' => ['nullable', 'string', 'max:20000'],
            'internal_notes' => ['nullable', 'string', 'max:20000'],
        ];
    }
}
