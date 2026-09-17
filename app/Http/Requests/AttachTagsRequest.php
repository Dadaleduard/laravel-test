<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AttachTagsRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'tag_ids' => 'required|array|min:1',
            'tag_ids.*' => 'integer|distinct|exists:tags,id',
        ];
    }

    public function messages()
    {
        return [
            'tag_ids.required' => 'Список тегов требуется.',
            'tag_ids.array' => 'Теги должны передаваться списком.',
            'tag_ids.*.exists' => 'Тег не найден.',
            'tag_ids.*.distinct' => 'Теги не должны повторяться.',
        ];
    }
}
