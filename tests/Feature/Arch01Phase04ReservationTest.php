<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;

class Arch01Phase04ReservationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_applies_early_booking_discount_for_30_days_advance()
    {
        $payload = [
            'products' => [
                [
                    'product_id' => 1,
                    'dates' => [
                        '2026-06-10', '2026-06-11', '2026-06-12', '2026-06-13',
                        '2026-06-14', '2026-06-15', '2026-06-16',
                    ],
                ],
            ],
        ];

        $response = $this->postJson('/api/arch_01/v4/reservation', $payload);

        $basePrice = 700;
        $earlyBookingDiscount = $basePrice * 0.05;
        $totalDiscount = $basePrice * 0.10 + $earlyBookingDiscount;

        $response->assertStatus(201)
            ->assertJsonPath('base_price', $basePrice)
            ->assertJson(['early_booking_discount_amount' => $earlyBookingDiscount])
            ->assertJson(['discount_reason' => 'volume-10% + early-booking-5%'])
            ->assertJson(['discount_amount' => $totalDiscount]);
    }

    #[Test]
    public function it_applies_early_booking_discount_for_60_days_advance()
    {
        $payload = [
            'products' => [
                [
                    'product_id' => 1,
                    'dates' => [
                        '2026-07-10', '2026-07-11', '2026-07-12', '2026-07-13',
                        '2026-07-14', '2026-07-15', '2026-07-16',
                    ],
                ],
            ],
        ];

        $response = $this->postJson('/api/arch_01/v4/reservation', $payload);

        $basePrice = 700;
        $earlyBookingDiscount = $basePrice * 0.10;

        $response->assertStatus(201)
            ->assertJsonPath('base_price', $basePrice)
            ->assertJson(['early_booking_discount_amount' => $earlyBookingDiscount])
            ->assertJson(['discount_reason' => 'volume-10% + early-booking-10%']);
    }

    #[Test]
    public function it_applies_seasonal_surcharge_for_high_season()
    {
        $payload = [
            'products' => [
                [
                    'product_id' => 1,
                    'dates' => [
                        '2026-07-10', '2026-07-11', '2026-07-12', '2026-07-13',
                        '2026-07-14', '2026-07-15', '2026-07-16',
                    ],
                ],
            ],
        ];

        $response = $this->postJson('/api/arch_01/v4/reservation', $payload);

        $basePrice = 700;
        $surcharge = $basePrice * 0.15;

        $response->assertStatus(201)
            ->assertJsonPath('base_price', $basePrice)
            ->assertJson(['seasonal_surcharge_amount' => $surcharge]);
    }

    #[Test]
    public function it_does_not_apply_seasonal_surcharge_for_low_season()
    {
        $payload = [
            'products' => [
                [
                    'product_id' => 1,
                    'dates' => [
                        '2026-06-10', '2026-06-11', '2026-06-12', '2026-06-13',
                        '2026-06-14', '2026-06-15', '2026-06-16',
                    ],
                ],
            ],
        ];

        $response = $this->postJson('/api/arch_01/v4/reservation', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('base_price', 700)
            ->assertJson(['seasonal_surcharge_amount' => 0]);
    }

    #[Test]
    public function it_combines_volume_discount_with_early_booking()
    {
        $payload = [
            'products' => [
                [
                    'product_id' => 1,
                    'dates' => [
                        '2026-06-10', '2026-06-11', '2026-06-12', '2026-06-13',
                        '2026-06-14', '2026-06-15', '2026-06-16',
                    ],
                ],
            ],
        ];

        $response = $this->postJson('/api/arch_01/v4/reservation', $payload);

        $basePrice = 700;
        $volumeDiscount = $basePrice * 0.10;
        $earlyBookingDiscount = $basePrice * 0.05;
        $totalDiscount = $volumeDiscount + $earlyBookingDiscount;

        $response->assertStatus(201)
            ->assertJsonPath('base_price', $basePrice)
            ->assertJson([
                'discount_amount' => $totalDiscount,
                'discount_reason' => 'volume-10% + early-booking-5%',
            ]);
    }

    #[Test]
    public function it_returns_reservation_with_all_phase04_fields_on_show()
    {
        $payload = [
            'products' => [
                [
                    'product_id' => 1,
                    'dates' => [
                        '2026-06-10', '2026-06-11', '2026-06-12', '2026-06-13',
                        '2026-06-14', '2026-06-15', '2026-06-16',
                    ],
                ],
            ],
        ];

        $createResponse = $this->postJson('/api/arch_01/v4/reservation', $payload);
        $reservationId = $createResponse->json('id');

        $response = $this->getJson("/api/arch_01/v4/reservation/{$reservationId}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'id', 'type', 'base_price', 'discount_amount', 'discount_reason',
                'tax_amount', 'tax_rate', 'commission_amount',
                'early_booking_discount_amount', 'seasonal_surcharge_amount',
                'extras', 'total',
            ]);
    }
}
