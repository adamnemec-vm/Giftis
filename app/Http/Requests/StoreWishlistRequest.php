<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreWishlistRequest extends FormRequest
{
    protected $errorBag = 'createWishlist';

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
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'occasion' => ['required', 'string', 'in:christmas,birthday,wedding,anniversary,other'],
            'event_date' => ['nullable', 'date'],
            'is_public' => ['boolean'],
        ];
    }
}
