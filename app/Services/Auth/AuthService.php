<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

final class AuthService
{
    /**
     * Autentica al usuario con número de cédula,
     * fecha de expedición del documento y contraseña.
     *
     * @throws ValidationException
     */
    public function login(LoginRequest $request): User
    {
        $user = User::query()
            ->where('document_number', trim((string) $request->input('document_number')))
            ->whereDate('document_issued_at', $request->input('document_issued_at'))
            ->where('is_active', true)
            ->first();

        if (! $user instanceof User || ! Hash::check((string) $request->input('password'), $user->password)) {
            throw ValidationException::withMessages([
                'document_number' => 'Los datos ingresados no coinciden con ningún usuario registrado.',
            ]);
        }

        $user->update(['last_login_at' => now()]);

        return $user;
    }
}
