<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Repositories\PestRoutes\Translators;

use App\Infrastructure\Repositories\PestRoutes\Translators\PestRoutesCustomerPreferencesTranslator;
use Tests\TestCase;
use Tests\Tools\PestRoutesData\SubscriptionData;
use Tests\Tools\TestValue;

class PestRoutesCustomerPreferencesTranslatorTest extends TestCase
{
    private PestRoutesCustomerPreferencesTranslator $translator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->translator = new PestRoutesCustomerPreferencesTranslator();
    }

    /**
     * @test
     */
    public function it_translates_customer_preferences_from_subscription(): void
    {
        $subscription = SubscriptionData::getTestData(1, [
            'preferredTech' => TestValue::EMPLOYEE_ID,
            'preferredDays' => '1',
            'preferredStart' => '09:00:00',
            'preferredEnd' => '17:00:00',
        ])->first();

        $result = $this->translator->toDomain($subscription);

        $this->assertEquals(TestValue::EMPLOYEE_ID, $result->getPreferredEmployeeId());
        $this->assertEquals(1, $result->getPreferredDay());
        $this->assertEquals('09:00:00', $result->getPreferredStart());
        $this->assertEquals('17:00:00', $result->getPreferredEnd());
    }
}
