<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePhotoRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return \Illuminate\Support\Facades\Auth::check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => 'sometimes|required|string|min:3|max:255',
            'description' => 'nullable|string|max:1000',
            'location' => 'nullable|string|max:255',
            'camera_model' => 'nullable|string|max:255',
            'settings' => 'nullable|string|max:500',
            'tags' => 'nullable|array|max:10',
            'tags.*' => 'string|max:50'
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'title.required' => 'Il titolo è obbligatorio.',
            'title.min' => 'Il titolo deve essere almeno 3 caratteri.',
            'title.max' => 'Il titolo non può superare 255 caratteri.',
            'description.max' => 'La descrizione non può superare 1000 caratteri.',
            'location.max' => 'La location non può superare 255 caratteri.',
            'camera_model.max' => 'Il modello della fotocamera non può superare 255 caratteri.',
            'settings.max' => 'Le impostazioni non possono superare 500 caratteri.',
            'tags.max' => 'Non puoi inserire più di 10 tag.',
            'tags.*.max' => 'Ogni tag non può superare 50 caratteri.'
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Convert tags string to array if needed
        if ($this->has('tags') && is_string($this->tags)) {
            $tags = array_map('trim', explode(',', $this->tags));
            $tags = array_filter($tags); // Remove empty tags
            $this->merge(['tags' => $tags]);
        }

        // Clean up optional fields
        if ($this->location === '') {
            $this->merge(['location' => null]);
        }

        if ($this->camera_model === '') {
            $this->merge(['camera_model' => null]);
        }

        if ($this->settings === '') {
            $this->merge(['settings' => null]);
        }
    }
}
