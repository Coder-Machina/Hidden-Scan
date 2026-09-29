<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $user = $this->user();
        $isAnonymous = !empty($user->pass_code);

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                $isAnonymous ? 'nullable' : 'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($user->id),
            ],
            'bio' => ['nullable', 'string', 'max:1000'],
            'favorite_genre' => ['nullable', 'string', 'max:100'],
            'reader_mode' => ['nullable', 'string', 'in:vertical,single'],
            'avatar_file' => ['nullable', 'image', 'max:8192'],
            'avatar_preset' => ['nullable', 'string', 'max:255'],
            'avatar_url' => ['nullable', 'string', 'max:2048'],
            'avatar_data' => ['nullable', 'string'],
            'banner_file' => ['nullable', 'image', 'max:10240'],
            'banner_url' => ['nullable', 'string', 'max:2048'],
            'banner_data' => ['nullable', 'string'],
        ];
    }
}
