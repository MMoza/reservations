<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;

class Phase04EquivalenceTest extends TestCase
{
    use RefreshDatabase;

    private array $payloadEarlyBooking30 = [
        'products' => [
            [
                'product_id' => 1,
                'dates' => ['2026-06-10', '2026-06-11', '2026-06-12', '2026-06-13', '2026-06-14', '2026-06-15', '2026-06-16'],
            ],
        ],
    ];

    private array $payloadEarlyBooking60 = [
        'products' => [
            [
                'product_id' => 1,
                'dates' => ['2026-07-10', '2026-07-11', '2026-07-12', '2026-07-13', '2026-07-14', '2026-07-15', '2026-07-16'],
            ],
        ],
    ];

    private array $payloadSeasonalHigh = [
        'products' => [
            [
                'product_id' => 1,
                'dates' => ['2026-07-10', '2026-07-11', '2026-07-12', '2026-07-13', '2026-07-14', '2026-07-15', '2026-07-16'],
            ],
        ],
    ];

    private array $payloadSeasonalLow = [
        'products' => [
            [
                'product_id' => 1,
                'dates' => ['2026-06-10', '2026-06-11', '2026-06-12', '2026-06-13', '2026-06-14', '2026-06-15', '2026-06-16'],
            ],
        ],
    ];

    private array $endpoints = [
        'arch_01/v4',
        'arch_02/v4',
        'arch_03/v4',
        'arch_04/v4',
    ];

    #[Test]
    public function all_architectures_return_equivalent_early_booking_30_days()
    {
        $responses = $this->createAcrossAllArchitectures($this->payloadEarlyBooking30);

        $reference = $responses->first()->json();

        $responses->skip(1)->each(function ($response) use ($reference) {
            $data = $response->json();

            $this->assertEquals($reference['base_price'], $data['base_price'], 'base_price mismatch');
            $this->assertEquals($reference['discount_amount'], $data['discount_amount'], 'discount_amount mismatch');
            $this->assertEquals($reference['early_booking_discount_amount'], $data['early_booking_discount_amount'], 'early_booking_discount_amount mismatch');
            $this->assertEquals($reference['seasonal_surcharge_amount'], $data['seasonal_surcharge_amount'], 'seasonal_surcharge_amount mismatch');
        });
    }

    #[Test]
    public function all_architectures_return_equivalent_early_booking_60_days()
    {
        $responses = $this->createAcrossAllArchitectures($this->payloadEarlyBooking60);

        $reference = $responses->first()->json();

        $responses->skip(1)->each(function ($response) use ($reference) {
            $data = $response->json();

            $this->assertEquals($reference['base_price'], $data['base_price'], 'base_price mismatch');
            $this->assertEquals($reference['discount_amount'], $data['discount_amount'], 'discount_amount mismatch');
            $this->assertEquals($reference['early_booking_discount_amount'], $data['early_booking_discount_amount'], 'early_booking_discount_amount mismatch');
            $this->assertEquals($reference['seasonal_surcharge_amount'], $data['seasonal_surcharge_amount'], 'seasonal_surcharge_amount mismatch');
        });
    }

    #[Test]
    public function all_architectures_return_equivalent_seasonal_high_season()
    {
        $responses = $this->createAcrossAllArchitectures($this->payloadSeasonalHigh);

        $reference = $responses->first()->json();

        $responses->skip(1)->each(function ($response) use ($reference) {
            $data = $response->json();

            $this->assertEquals($reference['base_price'], $data['base_price'], 'base_price mismatch');
            $this->assertEquals($reference['discount_amount'], $data['discount_amount'], 'discount_amount mismatch');
            $this->assertEquals($reference['early_booking_discount_amount'], $data['early_booking_discount_amount'], 'early_booking_discount_amount mismatch');
            $this->assertEquals($reference['seasonal_surcharge_amount'], $data['seasonal_surcharge_amount'], 'seasonal_surcharge_amount mismatch');
        });
    }

    #[Test]
    public function all_architectures_return_equivalent_seasonal_low_season()
    {
        $responses = $this->createAcrossAllArchitectures($this->payloadSeasonalLow);

        $reference = $responses->first()->json();

        $responses->skip(1)->each(function ($response) use ($reference) {
            $data = $response->json();

            $this->assertEquals($reference['base_price'], $data['base_price'], 'base_price mismatch');
            $this->assertEquals($reference['discount_amount'], $data['discount_amount'], 'discount_amount mismatch');
            $this->assertEquals($reference['early_booking_discount_amount'], $data['early_booking_discount_amount'], 'early_booking_discount_amount mismatch');
            $this->assertEquals($reference['seasonal_surcharge_amount'], $data['seasonal_surcharge_amount'], 'seasonal_surcharge_amount mismatch');
        });
    }

    #[Test]
    public function all_architectures_return_equivalent_show_response()
    {
        $created = $this->createAndMapAllArchitectures($this->payloadEarlyBooking30);

        $reference = $created->first();

        $created->each(function ($reservation, $prefix) use ($reference) {
            $response = $this->getJson("/api/{$prefix}/reservation/{$reservation['id']}");
            $response->assertStatus(200);

            $data = $response->json();
            $this->assertEquals($reference['base_price'], $data['base_price'], "base_price mismatch on GET for {$prefix}");
            $this->assertEquals($reference['discount_amount'], $data['discount_amount'], "discount_amount mismatch on GET for {$prefix}");
            $this->assertEquals($reference['early_booking_discount_amount'], $data['early_booking_discount_amount'], "early_booking_discount_amount mismatch on GET for {$prefix}");
            $this->assertEquals($reference['seasonal_surcharge_amount'], $data['seasonal_surcharge_amount'], "seasonal_surcharge_amount mismatch on GET for {$prefix}");
        });
    }

    private function createAcrossAllArchitectures(array $payload): \Illuminate\Support\Collection
    {
        return collect($this->endpoints)
            ->map(fn ($prefix) => $this->postJson("/api/{$prefix}/reservation", $payload))
            ->each(fn ($r) => $r->assertStatus(201));
    }

    private function createAndMapAllArchitectures(array $payload): \Illuminate\Support\Collection
    {
        return collect($this->endpoints)
            ->mapWithKeys(fn ($prefix) => [
                $prefix => $this->postJson("/api/{$prefix}/reservation", $payload)->json()
            ]);
    }
}
