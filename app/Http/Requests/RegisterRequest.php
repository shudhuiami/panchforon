<?php

namespace App\Http\Requests;

use App\Services\SettingsRepository;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    /**
     * Honours the "registration open" toggle on the admin settings screen.
     */
    public function authorize(): bool
    {
        return app(SettingsRepository::class)->boolean('registration_open', true);
    }

    protected function failedAuthorization(): never
    {
        throw new AuthorizationException('Registration is currently closed.');
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
        ];
    }
}
