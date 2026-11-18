<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var User $targetUser */
        $targetUser = $this->route('user');

        return $this->user()?->can('update', $targetUser) ?? false;
    }

    public function rules(): array
    {
        /** @var User $targetUser */
        $targetUser = $this->route('user');

        return [
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'regex:/^[a-zA-Z0-9._-]+$/', 'max:50', Rule::unique(User::class)->ignore($targetUser->id)],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique(User::class)->ignore($targetUser->id)],
            'role' => ['required', 'string', Rule::in(['operator', 'admin', 'user'])],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
