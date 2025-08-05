<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Helpers;

use Tests\TestCase;
use Illuminate\Support\Facades\Config;
use App\Infrastructure\Helpers\DataMask;

class DataMaskTest extends TestCase
{
    private const MASK = '*****';

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('data-mask.field_to_mask', [
            'routes.*.geometry',
        ]);
    }

    /**
     * @test
     *
     * @dataProvider dataMaskProvider
     */
    public function it_correctly_masks_data(array $input, array $expected): void
    {
        $maskedData = DataMask::mask($input);
        $this->assertEquals($expected, $maskedData);
    }

    public static function dataMaskProvider(): array
    {
        return [
            [
                ['routes' => [['geometry' => 'some value', 'anotherKey' => 'should not mask']]],
                ['routes' => [['geometry' => self::MASK, 'anotherKey' => 'should not mask']]],
            ],
            [
                ['routes' => [['subRoute' => ['geometry' => 'should not mask']]]],
                ['routes' => [['subRoute' => ['geometry' => 'should not mask']]]],
            ],
            [
                ['notRoutes' => ['geometry' => 'should not mask']],
                ['notRoutes' => ['geometry' => 'should not mask']],
            ],
            [
                [],
                [],
            ],
        ];
    }
}
