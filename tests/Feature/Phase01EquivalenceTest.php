<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;

class Phase01EquivalenceTest extends TestCase
{
    use RefreshDatabase;

    private array $payload = [
        'products' => [
            [
                'product_id' => 1,
                'dates' => ['2026-03-01', '2026-03-02', '2026-03-03'],
                'extras' => [
                    ['extra_id' => 10, 'dates' => ['2026-03-01', '2026-03-02', '2026-03-03']],
                    ['extra_id' => 11],
                ],
            ],
        ],
    ];

    private array $endpoints = [
        'arch_01/v1',
        'arch_02/v1',
        'arch_03/v1',
        'arch_04/v1',
    ];

    #[Test]
    public function all_architectures_return_equivalent_reservation_on_create()
    {
        $responses = collect($this->endpoints)
            ->map(fn ($prefix) => $this->postJson("/api/{$prefix}/reservation", $this->payload))
            ->each(fn ($r) => $r->assertStatus(201));

        $reference = $responses->first()->json();

        $responses->skip(1)->each(function ($response) use ($reference) {
            $data = $response->json();

            $this->assertEquals($reference['base_price'], $data['base_price'], 'base_price mismatch between architectures');
            $this->assertCount(count($reference['extras']), $data['extras'], 'extras count mismatch');

            foreach ($reference['extras'] as $index => $expectedExtra) {
                $actualExtra = $data['extras'][$index];
                $this->assertEquals($expectedExtra['name'], $actualExtra['name'], 'extra name mismatch');
                $this->assertEquals($expectedExtra['price'], $actualExtra['price'], 'extra price mismatch');
            }
        });
    }

    #[Test]
    public function all_architectures_return_equivalent_reservation_on_show()
    {
        $created = collect($this->endpoints)
            ->mapWithKeys(fn ($prefix) => [
                $prefix => $this->postJson("/api/{$prefix}/reservation", $this->payload)->json()
            ]);

        $reference = $created->first();

        $created->each(function ($reservation, $prefix) use ($reference) {
            $response = $this->getJson("/api/{$prefix}/reservation/{$reservation['id']}");

            $response->assertStatus(200);
            $data = $response->json();

            $this->assertEquals($reference['base_price'], $data['base_price'], "base_price mismatch on GET for {$prefix}");
            $this->assertCount(count($reference['extras']), $data['extras'], "extras count mismatch on GET for {$prefix}");
        });
    }
}
