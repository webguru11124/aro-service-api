<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\RouteOptimization\ValueObjects;

use App\Domain\RouteOptimization\ValueObjects\PropertyDetails;
use Tests\TestCase;

class PropertyDetailsTest extends TestCase
{
    /**
     * @test
     */
    public function it_correctly_sets_and_gets_property_details(): void
    {
        $landSqFt = 1000.0;
        $buildingSqFt = 800.0;
        $livingSqFt = 600.0;

        $propertyDetails = new PropertyDetails($landSqFt, $buildingSqFt, $livingSqFt);

        $this->assertEquals($landSqFt, $propertyDetails->getLandSqFt(), 'Land square footage does not match.');
        $this->assertEquals($buildingSqFt, $propertyDetails->getBuildingSqFt(), 'Building square footage does not match.');
        $this->assertEquals($livingSqFt, $propertyDetails->getLivingSqFt(), 'Living square footage does not match.');
    }
}
