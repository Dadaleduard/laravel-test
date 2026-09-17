<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UpdateTagRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    protected function prepareForValidation()
    {
        if (!$this->filled('slug') && is_string($this->input('name'))) {
            $this->merge(['slug' => Str::slug($this->input('name'))]);
        }
    }

    public function rules()
    {
        return [
            'name' => 'sometimes|required|string|max:255',
            'slug' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique('tags', 'slug')->ignore($this->route('tag')),
            ],
        ];
    }

    public function messages()
    {
        return [
            'name.required' => 'Название тега требуется.',
            'slug.unique' => 'Тег с таким slug уже существует.',
        ];
    }
}
