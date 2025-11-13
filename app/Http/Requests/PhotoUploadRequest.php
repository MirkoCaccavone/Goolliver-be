<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\File;

class PhotoUploadRequest extends FormRequest
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
            'contest_id' => 'required|exists:contests,id',
            'title' => 'required|string|min:3|max:255',
            'description' => 'nullable|string|max:1000',
            'photo' => [
                'required',
                'file',
                File::types(['jpg', 'jpeg', 'png', 'webp'])
                    ->min(100) // 100KB minimum
                    ->max(10240) // 10MB maximum
            ],
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
            'contest_id.required' => 'Il contest è obbligatorio.',
            'contest_id.exists' => 'Contest non valido.',
            'title.required' => 'Il titolo è obbligatorio.',
            'title.min' => 'Il titolo deve essere almeno 3 caratteri.',
            'title.max' => 'Il titolo non può superare 255 caratteri.',
            'description.max' => 'La descrizione non può superare 1000 caratteri.',
            'photo.required' => 'La foto è obbligatoria.',
            'photo.file' => 'Devi caricare un file valido.',
            'photo.mimes' => 'La foto deve essere in formato JPG, PNG o WEBP.',
            'photo.min' => 'La foto deve essere almeno 100KB.',
            'photo.max' => 'La foto non può superare 10MB.',
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

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Additional custom validation
            if ($this->hasFile('photo')) {
                $file = $this->file('photo');

                // Check file signature (magic bytes)
                $allowedSignatures = [
                    'image/jpeg' => ["\xFF\xD8\xFF"],
                    'image/png' => ["\x89\x50\x4E\x47\x0D\x0A\x1A\x0A"],
                    'image/webp' => ["RIFF", "WEBP"]
                ];

                $fileContent = file_get_contents($file->getPathname());
                $isValidFormat = false;

                foreach ($allowedSignatures as $mimeType => $signatures) {
                    foreach ($signatures as $signature) {
                        if (str_starts_with($fileContent, $signature)) {
                            $isValidFormat = true;
                            break 2;
                        }
                    }
                }

                if (!$isValidFormat) {
                    $validator->errors()->add('photo', 'Il file non è un\'immagine valida.');
                }

                // Check image dimensions
                $imageInfo = getimagesize($file->getPathname());
                if ($imageInfo) {
                    $width = $imageInfo[0];
                    $height = $imageInfo[1];

                    // Minimum dimensions - more reasonable for contest photos
                    if ($width < 640 || $height < 480) {
                        $validator->errors()->add('photo', 'L\'immagine deve essere almeno 640x480 pixel.');
                    }

                    // Maximum dimensions
                    if ($width > 8000 || $height > 8000) {
                        $validator->errors()->add('photo', 'L\'immagine non può superare 8000x8000 pixel.');
                    }

                    // Aspect ratio check - molto più permissivo per foto normali
                    $ratio = $width / $height;
                    if ($ratio < 0.2 || $ratio > 5.0) {
                        $validator->errors()->add('photo', 'L\'aspetto dell\'immagine è troppo estremo (troppo stretta o troppo larga).');
                    }
                } else {
                    $validator->errors()->add('photo', 'Impossibile leggere le dimensioni dell\'immagine.');
                }
            }
        });
    }
}
