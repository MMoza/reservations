<?php

namespace App\Architectures\A02_RepositoryPattern\Phase_03\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Architectures\A02_RepositoryPattern\Phase_03\Catalog\PricingRules;
use App\Architectures\A02_RepositoryPattern\Phase_03\Catalog\ProductCatalog;

class StoreReservationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'products' => ['required', 'array', 'min:1'],
            'products.*.product_id' => ['required', 'integer'],
            'products.*.dates'      => ['required', 'array', 'min:1'],
            'products.*.dates.*'    => ['required', 'date_format:Y-m-d'],
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

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $requirements = PricingRules::extraRequirements();
            $typeRestrictions = PricingRules::typeRestrictions();

            foreach ($this->input('products', []) as $productInput) {
                $nights = count($productInput['dates'] ?? []);

                $product = ProductCatalog::findProduct($productInput['product_id']);
                if ($product && isset($typeRestrictions[$product['product_type']])) {
                    $restriction = $typeRestrictions[$product['product_type']];
                    $minNights = $restriction['min_nights'];
                    $typeName = ucfirst($product['product_type']);

                    if ($nights < $minNights) {
                        $validator->errors()->add(
                            'products',
                            "{$typeName} bookings require a minimum of {$minNights} nights. Current reservation has {$nights}."
                        );
                    }
                }

                foreach ($productInput['extras'] ?? [] as $extraInput) {
                    $extraId = $extraInput['extra_id'];

                    if (isset($requirements[$extraId])) {
                        $extra = ProductCatalog::findExtra($extraId);
                        $minNights = $requirements[$extraId]['min_nights'];

                        if ($nights < $minNights) {
                            $extraName = $extra ? $extra['name'] : "Extra {$extraId}";
                            $validator->errors()->add(
                                'products',
                                "{$extraName} requires a minimum of {$minNights} nights. Current reservation has {$nights}."
                            );
                        }
                    }
                }
            }
        });
    }
}
