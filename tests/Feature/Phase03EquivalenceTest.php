<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;

class Phase03EquivalenceTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function all_architectures_return_equivalent_reservation_on_create()
    {
        $payload = [
            'products' => [
                [
                    'product_id' => 1,
                    'dates' => ['2026-03-01', '2026-03-02', '2026-03-03', '2026-03-04', '2026-03-05'],
                    'extras' => [
                        ['extra_id' => 10],
                    ],
                ],
            ],
        ];

        $responses = [];

        foreach (['arch_01/v3', 'arch_02/v3', 'arch_03/v3', 'arch_04/v3'] as $endpoint) {
            $response = $this->postJson("/api/{$endpoint}/reservation", $payload);
            $responses[$endpoint] = $response->json();
        }

        $stripIds = function ($data) {
            unset(
                $data['id'], $data['created_at'], $data['updated_at'], $data['total'],
                $data['early_booking_discount_amount'], $data['seasonal_surcharge_amount']
            );
            $extras = [];
            foreach ($data['extras'] ?? [] as $extra) {
                unset($extra['id'], $extra['reservation_id'], $extra['created_at'], $extra['updated_at']);
                $extras[] = $extra;
            }
            $data['extras'] = $extras;
            return $data;
        };

        $cleaned = array_map($stripIds, $responses);

        for ($i = 0; $i < count($cleaned) - 1; $i++) {
            $this->assertEquals(
                array_values($cleaned)[0],
                array_values($cleaned)[$i + 1],
                'Mismatch between first and ' . ($i + 2) . 'th architecture'
            );
        }
    }

    #[Test]
    public function all_architectures_return_equivalent_reservation_on_show()
    {
        $payload = [
            'products' => [
                [
                    'product_id' => 3,
                    'dates' => ['2026-03-01', '2026-03-02', '2026-03-03', '2026-03-04', '2026-03-05'],
                ],
            ],
        ];

        $ids = [];

        foreach (['arch_01/v3', 'arch_02/v3', 'arch_03/v3', 'arch_04/v3'] as $endpoint) {
            $response = $this->postJson("/api/{$endpoint}/reservation", $payload);
            $ids[$endpoint] = $response->json('id');
        }

        $responses = [];

        foreach ($ids as $endpoint => $id) {
            $response = $this->getJson("/api/{$endpoint}/reservation/{$id}");
            $responses[$endpoint] = $response->json();
        }

        $stripIds = function ($data) {
            unset(
                $data['id'], $data['created_at'], $data['updated_at'], $data['total'],
                $data['early_booking_discount_amount'], $data['seasonal_surcharge_amount']
            );
            $extras = [];
            foreach ($data['extras'] ?? [] as $extra) {
                unset($extra['id'], $extra['reservation_id'], $extra['created_at'], $extra['updated_at']);
                $extras[] = $extra;
            }
            $data['extras'] = $extras;
            return $data;
        };

        $cleaned = array_map($stripIds, $responses);

        for ($i = 0; $i < count($cleaned) - 1; $i++) {
            $this->assertEquals(
                array_values($cleaned)[0],
                array_values($cleaned)[$i + 1],
                'Mismatch between first and ' . ($i + 2) . 'th architecture'
            );
        }
    }
}
