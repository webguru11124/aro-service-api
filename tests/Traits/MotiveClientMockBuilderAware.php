<?php

declare(strict_types=1);

namespace Tests\Traits;

use Mockery\MockInterface;
use Tests\Tools\MotiveClientMockBuilder;

trait MotiveClientMockBuilderAware
{
    public function getMotiveClientMockBuilder(MockInterface|null $clientMock = null): MotiveClientMockBuilder
    {
        return new MotiveClientMockBuilder($clientMock);
    }
}
