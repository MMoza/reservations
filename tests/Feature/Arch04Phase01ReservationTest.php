<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class Arch04Phase01ReservationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_creates_a_reservation_with_multiple_products_and_extras()
    {
        $payload = [
            'products' => [
                [
                    'product_id' => 1,
                    'dates' => ['2026-03-01', '2026-03-02'],
                    'extras' => [
                        [
                            'extra_id' => 10,
                            'dates' => ['2026-03-01', '2026-03-02'],
                        ],
                        [
                            'extra_id' => 11,
                        ],
                    ],
                ],
            ],
        ];

        $response = $this->postJson('/api/arch_04/v1/reservation', $payload);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'id',
                'type',
                'base_price',
                'extras',
            ])
            ->assertJsonPath('base_price', 200)
            ->assertJsonCount(2, 'extras');

        $this->assertDatabaseCount('reservations', 1);
        $this->assertDatabaseCount('extras', 2);
    }

    #[Test]
    public function it_returns_a_reservation_with_loaded_extras()
    {
        $payload = [
            'products' => [
                [
                    'product_id' => 1,
                    'dates' => ['2026-03-01', '2026-03-02'],
                    'extras' => [
                        [
                            'extra_id' => 10,
                            'dates' => ['2026-03-01'],
                        ],
                    ],
                ],
            ],
        ];

        $createResponse = $this->postJson('/api/arch_04/v1/reservation', $payload);
        $reservationId = $createResponse->json('id');

        $response = $this->getJson("/api/arch_04/v1/reservation/{$reservationId}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'id',
                'type',
                'base_price',
                'extras',
            ])
            ->assertJsonPath('id', $reservationId)
            ->assertJsonCount(1, 'extras');
    }
}
