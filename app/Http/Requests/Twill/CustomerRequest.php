<?php

namespace App\Http\Requests\Twill;

use A17\Twill\Http\Requests\Admin\Request;
use Illuminate\Validation\Rule;

class CustomerRequest extends Request
{
    public function rulesForCreate(): array
    {
        return [
            'title' => ['required', 'string', 'max:200'],
            'company_name' => ['nullable', 'string', 'max:200'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('customers', 'email')],
            'phone' => ['nullable', 'string', 'max:50'],
            'country' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:10000'],
        ];
    }

    public function rulesForUpdate(): array
    {
        $id = $this->route('customer') ?? $this->route('id');

        return [
            'title' => ['required', 'string', 'max:200'],
            'company_name' => ['nullable', 'string', 'max:200'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('customers', 'email')->ignore($id)],
            'phone' => ['nullable', 'string', 'max:50'],
            'country' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:10000'],
        ];
    }
}
