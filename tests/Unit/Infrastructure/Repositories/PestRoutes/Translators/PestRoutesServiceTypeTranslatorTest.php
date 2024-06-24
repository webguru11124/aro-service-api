<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Repositories\PestRoutes\Translators;

use App\Domain\RouteOptimization\Enums\ServiceType;
use App\Infrastructure\Repositories\PestRoutes\Translators\PestRoutesServiceTypeTranslator;
use Aptive\PestRoutesSDK\Resources\ServiceTypes\ServiceType as PestRoutesServiceType;
use Tests\TestCase;
use Tests\Tools\PestRoutesData\ServiceTypeData;

class PestRoutesServiceTypeTranslatorTest extends TestCase
{
    private PestRoutesServiceTypeTranslator $serviceTypeTranslator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->serviceTypeTranslator = new PestRoutesServiceTypeTranslator();
    }

    /**
     * @test
     *
     * @dataProvider dataProvider
     */
    public function it_translates_pest_routes_service_type_to_domain(
        PestRoutesServiceType $pestRoutesServiceType,
        ServiceType $domainServiceType
    ): void {
        $result = $this->serviceTypeTranslator->toDomain($pestRoutesServiceType);

        $this->assertEquals($domainServiceType, $result);
    }

    public static function dataProvider(): iterable
    {
        yield [
            ServiceTypeData::getTestDataOfTypes(ServiceTypeData::PRO)->first(),
            ServiceType::REGULAR,
        ];
        yield [
            ServiceTypeData::getTestDataOfTypes(ServiceTypeData::PRO_PLUS)->first(),
            ServiceType::REGULAR,
        ];
        yield [
            ServiceTypeData::getTestDataOfTypes(ServiceTypeData::PREMIUM)->first(),
            ServiceType::REGULAR,
        ];
        yield [
            ServiceTypeData::getTestDataOfTypes(ServiceTypeData::MOSQUITO)->first(),
            ServiceType::REGULAR,
        ];
        yield [
            ServiceTypeData::getTestDataOfTypes(ServiceTypeData::QUARTERLY_SERVICE)->first(),
            ServiceType::REGULAR,
        ];
        yield [
            ServiceTypeData::getTestDataOfTypes(ServiceTypeData::INITIAL)->first(),
            ServiceType::INITIAL,
        ];
        yield [
            ServiceTypeData::getTestDataOfTypes(ServiceTypeData::RESERVICE)->first(),
            ServiceType::RESERVICE,
        ];
    }
}
