<?php

namespace App\Http\Requests\Course;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCourseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('course'));
    }

    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'subtitle' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'thumbnail_url' => ['nullable', 'url', 'max:2048'],
            'level' => ['sometimes', 'required', 'in:beginner,intermediate,advanced'],
            'language' => ['nullable', 'string', 'max:10'],
            'price' => ['sometimes', 'required', 'numeric', 'min:0', 'max:9999'],
            'requirements' => ['nullable', 'array'],
            'requirements.*' => ['string', 'max:255'],
            'what_you_will_learn' => ['nullable', 'array'],
            'what_you_will_learn.*' => ['string', 'max:255'],
            'status' => ['sometimes', 'required', 'in:draft,published'],
        ];
    }
}
