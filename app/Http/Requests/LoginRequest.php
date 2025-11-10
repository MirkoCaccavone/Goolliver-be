<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class LoginRequest extends FormRequest
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
            'email' => [
                'required',
                'string',
                'email:rfc,dns',
                'max:255',
                'regex:/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/'
            ],
            'password' => [
                'required',
                'string',
                'min:6',
                'max:128'
            ]
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'email.required' => 'L\'indirizzo email è obbligatorio.',
            'email.email' => 'Inserisci un indirizzo email valido.',
            'email.regex' => 'Il formato dell\'email non è valido.',
            'password.required' => 'La password è obbligatoria.',
            'password.min' => 'La password deve contenere almeno 6 caratteri.',
            'password.max' => 'La password non può superare 128 caratteri.'
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => strtolower(trim($this->email ?? '')),
        ]);
    }

    /**
     * Get sanitized data
     */
    public function getSanitizedData(): array
    {
        return [
            'email' => filter_var($this->validated()['email'], FILTER_SANITIZE_EMAIL),
            'password' => $this->validated()['password']
        ];
    }
}
