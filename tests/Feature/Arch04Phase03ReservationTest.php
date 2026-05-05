<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;

class Arch04Phase03ReservationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_applies_hotel_tax_and_no_commission()
    {
        $payload = [
            'products' => [
                [
                    'product_id' => 1,
                    'dates' => ['2026-03-01', '2026-03-02', '2026-03-03', '2026-03-04', '2026-03-05'],
                ],
            ],
        ];

        $response = $this->postJson('/api/arch_04/v3/reservation', $payload);

        $basePrice = 500;
        $tax = $basePrice * 0.10;

        $response->assertStatus(201)
            ->assertJson(['base_price' => $basePrice])
            ->assertJson(['tax_amount' => $tax])
            ->assertJsonPath('tax_rate', 'Tax 10%')
            ->assertJson(['commission_amount' => 0.0]);
    }

    #[Test]
    public function it_applies_event_tax_and_commission()
    {
        $payload = [
            'products' => [
                [
                    'product_id' => 3,
                    'dates' => ['2026-03-01', '2026-03-02', '2026-03-03', '2026-03-04', '2026-03-05'],
                ],
            ],
        ];

        $response = $this->postJson('/api/arch_04/v3/reservation', $payload);

        $basePrice = 300 * 5;
        $tax = $basePrice * 0.05;
        $commission = $basePrice * 0.03;

        $response->assertStatus(201)
            ->assertJson(['base_price' => $basePrice])
            ->assertJson(['tax_amount' => $tax])
            ->assertJsonPath('tax_rate', 'Tax 5%')
            ->assertJson(['commission_amount' => $commission]);
    }

    #[Test]
    public function it_rejects_event_with_less_than_3_nights()
    {
        $payload = [
            'products' => [
                [
                    'product_id' => 3,
                    'dates' => ['2026-03-01', '2026-03-02'],
                ],
            ],
        ];

        $response = $this->postJson('/api/arch_04/v3/reservation', $payload);

        $response->assertStatus(422)
            ->assertJsonStructure(['message']);
    }
}
