<?php

declare(strict_types=1);

namespace App\Http\Requests\Api;

use App\Models\Institusi;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

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
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
            'slug' => ['required', 'string', 'exists:institusis,slug'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'password.required' => 'Password wajib diisi.',
            'slug.required' => 'Kode institusi wajib diisi.',
            'slug.exists' => 'Kode institusi tidak valid.',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if (! $validator->errors()->has('email') && ! $validator->errors()->has('slug')) {
                $this->validateUserInstitusiSlug($validator);
            }
        });
    }

    /**
     * Validate that the user belongs to the specified institusi.
     */
    protected function validateUserInstitusiSlug(Validator $validator): void
    {
        $email = $this->input('email');
        $slug = $this->input('slug');

        $user = User::where('email', $email)->with('institusi')->first();

        if ($user && $user->institusi && $user->institusi->slug !== $slug) {
            $validator->errors()->add('slug', 'Akun Anda tidak terdaftar pada institusi ini.');
        }
    }
}
