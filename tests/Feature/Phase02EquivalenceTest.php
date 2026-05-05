<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;

class Phase02EquivalenceTest extends TestCase
{
    use RefreshDatabase;

    private array $payloadVolume7 = [
        'products' => [
            [
                'product_id' => 1,
                'dates' => ['2026-03-01', '2026-03-02', '2026-03-03', '2026-03-04', '2026-03-05', '2026-03-06', '2026-03-07'],
            ],
        ],
    ];

    private array $payloadVolume14 = [
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

    private array $payloadCombined = [
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

    private array $payloadVolumeAndCombined = [
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

    private array $endpoints = [
        'arch_01/v2',
        'arch_02/v2',
        'arch_03/v2',
        'arch_04/v2',
    ];

    #[Test]
    public function all_architectures_return_equivalent_volume_discount_7_nights()
    {
        $this->assertNetPriceEquivalence($this->payloadVolume7, 700, 0.10);
    }

    #[Test]
    public function all_architectures_return_equivalent_volume_discount_14_nights()
    {
        $this->assertNetPriceEquivalence($this->payloadVolume14, 1400, 0.20);
    }

    #[Test]
    public function all_architectures_return_equivalent_combined_promotion()
    {
        $responses = $this->createAcrossAllArchitectures($this->payloadCombined);

        $reference = $responses->first()->json();

        $responses->skip(1)->each(function ($response) use ($reference) {
            $data = $response->json();

            $referenceNet = round($reference['base_price'] - $reference['discount_amount'], 2);
            $dataNet = round($data['base_price'] - $data['discount_amount'], 2);
            $this->assertEquals($referenceNet, $dataNet, 'net price mismatch');
            $this->assertEquals(
                round($reference['discount_amount'], 2),
                round($data['discount_amount'], 2),
                'discount_amount mismatch'
            );
        });
    }

    #[Test]
    public function all_architectures_return_equivalent_volume_and_combined()
    {
        $responses = $this->createAcrossAllArchitectures($this->payloadVolumeAndCombined);

        $reference = $responses->first()->json();

        $responses->skip(1)->each(function ($response) use ($reference) {
            $data = $response->json();

            $referenceNet = round($reference['base_price'] - $reference['discount_amount'], 2);
            $dataNet = round($data['base_price'] - $data['discount_amount'], 2);
            $this->assertEquals($referenceNet, $dataNet, 'net price mismatch');
            $this->assertEquals(
                round($reference['discount_amount'], 2),
                round($data['discount_amount'], 2),
                'discount_amount mismatch'
            );
        });
    }

    #[Test]
    public function all_architectures_return_equivalent_show_response()
    {
        $created = $this->createAndMapAllArchitectures($this->payloadVolume7);

        $reference = $created->first();

        $created->each(function ($reservation, $prefix) use ($reference) {
            $response = $this->getJson("/api/{$prefix}/reservation/{$reservation['id']}");
            $response->assertStatus(200);

            $data = $response->json();

            $referenceNet = round($reference['base_price'] - $reference['discount_amount'], 2);
            $dataNet = round($data['base_price'] - $data['discount_amount'], 2);
            $this->assertEquals($referenceNet, $dataNet, "net price mismatch on GET for {$prefix}");
            $this->assertEquals(
                round($reference['discount_amount'], 2),
                round($data['discount_amount'], 2),
                "discount_amount mismatch on GET for {$prefix}"
            );
        });
    }

    private function assertNetPriceEquivalence(array $payload, float $expectedOriginal, float $discountPercentage): void
    {
        $responses = $this->createAcrossAllArchitectures($payload);

        $reference = $responses->first()->json();

        $responses->skip(1)->each(function ($response) use ($reference, $expectedOriginal, $discountPercentage) {
            $data = $response->json();

            $referenceNet = round($reference['base_price'] - $reference['discount_amount'], 2);
            $dataNet = round($data['base_price'] - $data['discount_amount'], 2);
            $expectedNet = round($expectedOriginal * (1 - $discountPercentage), 2);

            $this->assertEquals($expectedNet, $dataNet, 'net price mismatch');
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
