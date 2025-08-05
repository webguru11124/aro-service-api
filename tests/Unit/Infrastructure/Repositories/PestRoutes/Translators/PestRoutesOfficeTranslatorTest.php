<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Repositories\PestRoutes\Translators;

use App\Infrastructure\Repositories\PestRoutes\Translators\PestRoutesOfficeTranslator;
use Aptive\PestRoutesSDK\Resources\Offices\Office as PestRoutesOffice;
use Tests\TestCase;
use Tests\Tools\PestRoutesData\OfficeData;
use Tests\Tools\TestValue;

class PestRoutesOfficeTranslatorTest extends TestCase
{
    private PestRoutesOfficeTranslator $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subject = new PestRoutesOfficeTranslator();
    }

    /**
     * @test
     */
    public function it_translates_office_from_pest_routes_to_domain(): void
    {
        /** @var PestRoutesOffice $pestRoutesOffice */
        $pestRoutesOffice = OfficeData::getTestData(1, [
            'officeID' => TestValue::OFFICE_ID,
            'officeName' => TestValue::OFFICE_NAME,
            'timeZone' => TestValue::TIME_ZONE,
        ])->first();

        $result = $this->subject->toDomain($pestRoutesOffice);

        $this->assertEquals(TestValue::OFFICE_ID, $result->getId());
        $this->assertEquals(TestValue::OFFICE_NAME, $result->getName());
        $this->assertEquals(TestValue::TIME_ZONE, $result->getTimeZone()->getName());
    }
}
