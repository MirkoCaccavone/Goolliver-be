<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class NotificationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'title' => [
                'required',
                'string',
                'min:3',
                'max:255',
                'regex:/^[a-zA-Z0-9À-ÿ\s\-_.,!?🎉📸🔔⏰🥇🥈🥉🏆💫⭐]+$/u'  // Permette emojis comuni
            ],
            'message' => [
                'required',
                'string',
                'min:10',
                'max:1000',
                'regex:/^[a-zA-Z0-9À-ÿ\s\-_.,!?()"\'\n\r🎉📸🔔⏰🥇🥈🥉🏆💫⭐]+$/u'
            ],
            'type' => [
                'required',
                'string',
                'in:welcome,contest,reminder,success,like,generic'
            ],
            'data' => [
                'nullable',
                'array',
                'max:10'  // Max 10 elementi nell'array
            ],
            'data.*' => [
                'string',
                'max:500'  // Max 500 caratteri per elemento
            ]
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'title.required' => 'Il titolo della notifica è obbligatorio.',
            'title.regex' => 'Il titolo contiene caratteri non consentiti.',
            'message.required' => 'Il messaggio della notifica è obbligatorio.',
            'message.min' => 'Il messaggio deve contenere almeno 10 caratteri.',
            'message.regex' => 'Il messaggio contiene caratteri non consentiti.',
            'type.in' => 'Il tipo di notifica non è valido.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'title' => trim($this->title ?? ''),
            'message' => trim($this->message ?? ''),
            'type' => strtolower(trim($this->type ?? '')),
        ]);
    }

    /**
     * Get sanitized data
     */
    public function getSanitizedData(): array
    {
        $validated = $this->validated();

        return [
            'title' => htmlspecialchars($validated['title'], ENT_QUOTES, 'UTF-8'),
            'message' => htmlspecialchars($validated['message'], ENT_QUOTES, 'UTF-8'),
            'type' => $validated['type'],
            'data' => $this->sanitizeDataArray($validated['data'] ?? [])
        ];
    }

    /**
     * Sanitize data array
     */
    private function sanitizeDataArray(array $data): array
    {
        $sanitized = [];

        foreach ($data as $key => $value) {
            $cleanKey = htmlspecialchars($key, ENT_QUOTES, 'UTF-8');
            $cleanValue = is_string($value)
                ? htmlspecialchars($value, ENT_QUOTES, 'UTF-8')
                : $value;

            $sanitized[$cleanKey] = $cleanValue;
        }

        return $sanitized;
    }
}
