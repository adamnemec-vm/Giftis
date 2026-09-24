<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InviteGroupMemberRequest extends FormRequest
{
    protected $errorBag = 'inviteMember';

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email', 'max:255'],
        ];
    }
}
