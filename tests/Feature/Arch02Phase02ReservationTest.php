<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;

class Arch02Phase02ReservationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_applies_volume_discount_for_7_or_more_nights()
    {
        $payload = [
            'products' => [
                [
                    'product_id' => 1,
                    'dates' => ['2026-03-01', '2026-03-02', '2026-03-03', '2026-03-04', '2026-03-05', '2026-03-06', '2026-03-07'],
                ],
            ],
        ];

        $response = $this->postJson('/api/arch_02/v2/reservation', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('base_price', 700)
            ->assertJsonPath('discount_amount', 70)
            ->assertJsonPath('discount_reason', 'volume-10%');
    }

    #[Test]
    public function it_applies_volume_discount_for_14_or_more_nights()
    {
        $payload = [
            'products' => [
                [
                    'product_id' => 1,
                    'dates' => [
                        '2026-03-01', '2026-03-02', '2026-03-03', '2026-03-04',
                        '2026-03-05', '2026-03-06', '2026-03-07', '2026-03-08',
                        '2026-03-09', '2026-03-10', '2026-03-11', '2026-03-12',
                        '2026-03-13', '2026-03-14',
                    ],
                ],
            ],
        ];

        $response = $this->postJson('/api/arch_02/v2/reservation', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('base_price', 1400)
            ->assertJsonPath('discount_amount', 280)
            ->assertJsonPath('discount_reason', 'volume-20%');
    }

    #[Test]
    public function it_applies_combined_promo_when_both_products_are_present()
    {
        $payload = [
            'products' => [
                [
                    'product_id' => 1,
                    'dates' => ['2026-03-01', '2026-03-02', '2026-03-03'],
                ],
                [
                    'product_id' => 2,
                    'dates' => ['2026-03-01', '2026-03-02', '2026-03-03'],
                ],
            ],
        ];

        $response = $this->postJson('/api/arch_02/v2/reservation', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('base_price', 840)
            ->assertJsonPath('discount_amount', 42)
            ->assertJsonPath('discount_reason', 'combined-promo-5%');
    }

    #[Test]
    public function it_combines_volume_and_combined_discounts()
    {
        $payload = [
            'products' => [
                [
                    'product_id' => 1,
                    'dates' => [
                        '2026-03-01', '2026-03-02', '2026-03-03', '2026-03-04',
                        '2026-03-05', '2026-03-06', '2026-03-07',
                    ],
                ],
                [
                    'product_id' => 2,
                    'dates' => [
                        '2026-03-01', '2026-03-02', '2026-03-03', '2026-03-04',
                        '2026-03-05', '2026-03-06', '2026-03-07',
                    ],
                ],
            ],
        ];

        $response = $this->postJson('/api/arch_02/v2/reservation', $payload);

        $basePrice = 100 * 7 + 180 * 7;
        $volumeDiscount = $basePrice * 0.20;
        $combinedDiscount = $basePrice * 0.05;
        $totalDiscount = $volumeDiscount + $combinedDiscount;

        $response->assertStatus(201)
            ->assertJsonPath('base_price', $basePrice)
            ->assertJson([
                'discount_amount' => (string) $totalDiscount,
                'discount_reason' => 'volume-20% + combined-promo-5%',
            ]);
    }

    #[Test]
    public function it_rejects_reservation_below_minimum_price()
    {
        $payload = [
            'products' => [
                [
                    'product_id' => 1,
                    'dates' => ['2026-03-01', '2026-03-02', '2026-03-03'],
                ],
            ],
        ];

        $response = $this->postJson('/api/arch_02/v2/reservation', $payload);

        $response->assertStatus(422)
            ->assertJsonStructure(['message']);
    }

    #[Test]
    public function it_rejects_spa_extra_with_less_than_3_nights()
    {
        $payload = [
            'products' => [
                [
                    'product_id' => 1,
                    'dates' => ['2026-03-01', '2026-03-02'],
                    'extras' => [
                        ['extra_id' => 11],
                    ],
                ],
            ],
        ];

        $response = $this->postJson('/api/arch_02/v2/reservation', $payload);

        $response->assertStatus(422)
            ->assertJsonStructure(['message']);
    }

    #[Test]
    public function it_allows_spa_extra_with_3_or_more_nights()
    {
        $payload = [
            'products' => [
                [
                    'product_id' => 1,
                    'dates' => ['2026-03-01', '2026-03-02', '2026-03-03', '2026-03-04', '2026-03-05'],
                    'extras' => [
                        ['extra_id' => 11],
                    ],
                ],
            ],
        ];

        $response = $this->postJson('/api/arch_02/v2/reservation', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('extras.0.name', 'Habitación estándar - Hotel A - Spa')
            ->assertJsonPath('extras.0.price', 50);
    }

    #[Test]
    public function it_returns_reservation_with_discount_fields_on_show()
    {
        $payload = [
            'products' => [
                [
                    'product_id' => 1,
                    'dates' => ['2026-03-01', '2026-03-02', '2026-03-03', '2026-03-04', '2026-03-05', '2026-03-06', '2026-03-07'],
                ],
            ],
        ];

        $createResponse = $this->postJson('/api/arch_02/v2/reservation', $payload);
        $reservationId = $createResponse->json('id');

        $response = $this->getJson("/api/arch_02/v2/reservation/{$reservationId}");

        $response->assertStatus(200)
            ->assertJsonStructure(['id', 'type', 'base_price', 'discount_amount', 'discount_reason', 'extras', 'total']);
    }
}
