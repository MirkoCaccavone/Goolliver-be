<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
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
            'name' => [
                'required',
                'string',
                'min:2',
                'max:255',
                'regex:/^[a-zA-ZÀ-ÿ\s\'.-]+$/u'  // Solo lettere, spazi, apostrofi, trattini
            ],
            'email' => [
                'required',
                'string',
                'email:rfc,dns',
                'max:255',
                'unique:users,email',
                'regex:/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/'
            ],
            'password' => [
                'required',
                'string',
                'confirmed',
                Password::min(8)
                    ->mixedCase()
                    ->numbers()
                    ->symbols()
                    ->uncompromised()
            ],
            'phone' => [
                'nullable',
                'string',
                'regex:/^[\+]?[0-9\s\-\(\)]{8,20}$/'
            ]
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Il nome è obbligatorio.',
            'name.regex' => 'Il nome può contenere solo lettere, spazi, apostrofi e trattini.',
            'email.unique' => 'Questo indirizzo email è già registrato.',
            'password.confirmed' => 'La conferma password non corrisponde.',
            'phone.regex' => 'Il numero di telefono non è nel formato corretto.'
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim($this->name ?? ''),
            'email' => strtolower(trim($this->email ?? '')),
            'phone' => preg_replace('/[^\d\+\-\(\)\s]/', '', $this->phone ?? ''),
        ]);
    }

    /**
     * Get sanitized data
     */
    public function getSanitizedData(): array
    {
        $validated = $this->validated();

        return [
            'name' => htmlspecialchars($validated['name'], ENT_QUOTES, 'UTF-8'),
            'email' => filter_var($validated['email'], FILTER_SANITIZE_EMAIL),
            'password' => $validated['password'],
            'phone' => $validated['phone'] ?? null
        ];
    }
}
