<?php

namespace App\Http\Requests\Twill;

use A17\Twill\Http\Requests\Admin\Request;
use App\Models\Inquiry;
use Illuminate\Validation\Rule;

class InquiryRequest extends Request
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
            'status' => ['required', Rule::in(Inquiry::STATUSES)],
            'internal_notes' => ['nullable', 'string', 'max:20000'],
        ];
    }
}
