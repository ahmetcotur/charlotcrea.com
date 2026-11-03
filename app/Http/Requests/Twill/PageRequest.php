<?php

namespace App\Http\Requests\Twill;

class PageRequest extends ContentRequest
{
    public function rulesForCreate(): array
    {
        return $this->rulesForUpdate();
    }

    public function rulesForUpdate(): array
    {
        $rules = parent::rulesForUpdate();
        $rules['title.en'] = ['nullable', 'required_without:title.tr', 'string', 'max:200'];
        $rules['title.tr'] = ['nullable', 'required_without:title.en', 'string', 'max:200'];
        $rules['heading.*'] = ['nullable', 'string', 'max:1000'];
        $rules['intro.*'] = ['nullable', 'string', 'max:5000'];
        $rules['activeLanguage'] = ['sometimes', 'in:en,tr'];
        $rules['body.*'] = ['nullable', 'string', 'max:200000'];
        $rules['medias.gallery'] = ['sometimes', 'array', 'max:100'];
        $rules['video_url'] = ['nullable', 'string', 'max:1000', 'regex:~^(?:/assets/[^\s]+\.mp4|https://[^\s]+\.mp4(?:\?[^\s]*)?)$~i'];

        return $rules;
    }
}
