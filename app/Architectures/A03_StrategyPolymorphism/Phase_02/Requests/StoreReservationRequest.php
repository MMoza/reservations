<?php

namespace App\Architectures\A03_StrategyPolymorphism\Phase_02\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Architectures\A03_StrategyPolymorphism\Phase_02\Catalog\PricingRules;
use App\Architectures\A03_StrategyPolymorphism\Phase_02\Domain\Catalog\ProductCatalog;

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

            foreach ($this->input('products', []) as $productInput) {
                $nights = count($productInput['dates'] ?? []);

                foreach ($productInput['extras'] ?? [] as $extraInput) {
                    $extraId = $extraInput['extra_id'];

                    if (isset($requirements[$extraId])) {
                        $extra = ProductCatalog::findExtrasByIds([$extraId]);
                        $minNights = $requirements[$extraId]['min_nights'];

                        if ($nights < $minNights) {
                            $extraName = $extra ? ($extra[$extraId]['name'] ?? "Extra {$extraId}") : "Extra {$extraId}";
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
