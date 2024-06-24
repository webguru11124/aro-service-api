<?php

declare(strict_types=1);

namespace Tests\Traits;

use Tests\Tools\PestRoutesClientMockBuilder;

trait PestRoutesClientMockBuilderAware
{
    public function getPestRoutesClientMockBuilder(): PestRoutesClientMockBuilder
    {
        return new PestRoutesClientMockBuilder();
    }
}
