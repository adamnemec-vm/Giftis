<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreGiftItemRequest extends FormRequest
{
    protected $errorBag = 'addItem';

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
            'url' => ['nullable', 'url', 'max:2000'],
            'price' => ['nullable', 'numeric', 'min:0', 'required_if:is_group_gift,1'],
            'currency' => ['nullable', 'string', 'max:10'],
            'description' => ['nullable', 'string', 'max:1000'],
            'priority' => ['required', 'string', 'in:low,medium,high'],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:5120'],
            'image_url' => ['nullable', 'url', 'max:2000'],
            'is_group_gift' => ['boolean'],
        ];
    }
}
