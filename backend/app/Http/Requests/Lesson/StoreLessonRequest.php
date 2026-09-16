<?php

namespace App\Http\Requests\Lesson;

use App\Models\Lesson;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreLessonRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('section')->course);
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'youtube_url' => ['required', 'string', 'max:255'],
            'content' => ['nullable', 'string'],
            'position' => ['nullable', 'integer', 'min:0'],
            'duration_seconds' => ['nullable', 'integer', 'min:0'],
            'is_preview' => ['nullable', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $url = $this->input('youtube_url');

            if ($url && ! Lesson::extractYoutubeId($url)) {
                $validator->errors()->add('youtube_url', 'La URL de YouTube no es válida.');
            }
        });
    }
}
