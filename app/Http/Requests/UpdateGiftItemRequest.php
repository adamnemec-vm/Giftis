<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateGiftItemRequest extends FormRequest
{
    protected $errorBag = 'updateItem';

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

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $item = $this->route('item');

            if (! $item || $item->contributed_total <= 0) {
                return;
            }

            if (! $this->boolean('is_group_gift')) {
                $validator->errors()->add('is_group_gift', 'Nelze vypnout skupinový dárek, už na něj někdo přispěl.');
            }

            $price = $this->input('price');
            if ($price !== null && (float) $price < $item->contributed_total) {
                $validator->errors()->add('price', 'Cenu nelze snížit pod už vybranou částku od přispěvatelů.');
            }
        });
    }
}
