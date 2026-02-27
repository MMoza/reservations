<?php

namespace App\Architectures\A01_MonolithicEloquent\Phase_01\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreReservationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Root
            'products' => ['required', 'array', 'min:1'],

            // Product level
            'products.*.product_id' => ['required', 'integer'],
            'products.*.dates'      => ['required', 'array', 'min:1'],
            'products.*.dates.*'    => ['required', 'date_format:Y-m-d'],

            // Extras (optional)
            'products.*.extras' => ['nullable', 'array'],

            'products.*.extras.*.extra_id' => ['required', 'integer'],
            'products.*.extras.*.dates'    => ['nullable', 'array'],
            'products.*.extras.*.dates.*'  => ['required_with:products.*.extras.*.dates', 'date_format:Y-m-d'],
        ];
    }

    public function messages(): array
    {
        return [
            'products.required'                       => 'At least one product is required.',
            'products.*.dates.required'               => 'Each product must contain at least one date.',
            'products.*.dates.*.date_format'          => 'Dates must be in Y-m-d format.',
            'products.*.extras.*.dates.*.date_format' => 'Extra dates must be in Y-m-d format.',
        ];
    }
}