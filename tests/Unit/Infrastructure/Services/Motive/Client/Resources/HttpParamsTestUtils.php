<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Services\Motive\Client\Resources;

use App\Infrastructure\Services\Motive\Client\Resources\AbstractHttpParams;
use PHPUnit\Framework\Constraint\ArrayHasKey;

trait HttpParamsTestUtils
{
    private AbstractHttpParams $params;

    protected function setUp(): void
    {
        parent::setUp();

        $this->params = $this->getParams();
    }

    /**
     * @test
     */
    public function it_provides_pagination_data(): void
    {
        $array = $this->getParams()->toArray();

        $this->assertArrayHasKey('page_no', $array);
        $this->assertArrayHasKey('per_page', $array);
    }

    /**
     * @test
     */
    abstract public function it_transforms_params_to_array_correctly(): void;

    abstract private function getParams(): AbstractHttpParams;

    abstract public static function arrayHasKey(int|string $key): ArrayHasKey;

    protected function tearDown(): void
    {
        parent::tearDown();
        unset($this->params);
    }
}
