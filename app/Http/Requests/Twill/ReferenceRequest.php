<?php

namespace App\Http\Requests\Twill;

use A17\Twill\Http\Requests\Admin\Request;

class ReferenceRequest extends Request
{
    public function rulesForCreate(): array
    {
        return ['title.en' => ['required', 'string', 'max:200']];
    }

    public function rulesForUpdate(): array
    {
        return ['title.en' => ['required', 'string', 'max:200'], 'website_url' => ['nullable', 'url:http,https', 'max:1000']];
    }
}
