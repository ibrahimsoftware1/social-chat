<?php

namespace App\Http\Requests\Conversation;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreConversationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in(['private', 'group'])],
            'name' => ['required_if:type,group', 'nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:500'],
            'avatar' => ['nullable', 'image', 'max:2048'],
            'user_ids' => ['required', 'array', 'min:1'],
            'user_ids.*' => ['required', 'exists:users,id', 'distinct'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required_if' => 'Group conversations require a name.',
            'user_ids.min' => 'At least one participant is required.',
        ];
    }
}
