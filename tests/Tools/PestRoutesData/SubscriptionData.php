<?php

declare(strict_types=1);

namespace Tests\Tools\PestRoutesData;

use Aptive\PestRoutesSDK\Resources\Subscriptions\Subscription;

class SubscriptionData extends AbstractTestPestRoutesData
{
    protected static function getRequiredEntityClass(): string
    {
        return Subscription::class;
    }

    protected static function getSignature(): array
    {
        return [
            'subscriptionID' => random_int(10000, 99999),
            'customerID' => '2868285',
            'billToAccountID' => '2868285',
            'officeID' => '39',
            'dateAdded' => '2023-01-26 13:10:32',
            'contractAdded' => '2023-01-26 14:13:10',
            'active' => '1',
            'activeText' => 'Active',
            'initialQuote' => '399.00',
            'initialDiscount' => '399.00',
            'initialServiceTotal' => '0.00',
            'yifDiscount' => '0.00',
            'recurringCharge' => '129.00',
            'contractValue' => '268.00',
            'annualRecurringValue' => '258.00',
            'billingFrequency' => '-1',
            'frequency' => '180',
            'followupService' => '30',
            'agreementLength' => '24',
            'nextService' => '2023-01-26',
            'lastCompleted' => '0000-00-00',
            'serviceID' => '1800',
            'serviceType' => 'Pro',
            'soldBy' => '106238',
            'soldBy2' => '0',
            'soldBy3' => '0',
            'preferredTech' => '529649',
            'addedBy' => '0',
            'initialAppointmentID' => '27281096',
            'initialStatus' => '0',
            'initialStatusText' => 'Pending',
            'dateCancelled' => '0000-00-00 00:00:00',
            'dateUpdated' => '2024-04-12 09:42:43',
            'cxlNotes' => '',
            'subscriptionLink' => null,
            'poNumber' => '',
            'appointmentIDs' => '27249316,27249739,27250031,27250406,27263842,27269587,27281096',
            'completedAppointmentIDs' => null,
            'leadID' => null,
            'leadDateAdded' => null,
            'leadUpdated' => null,
            'leadAddedBy' => null,
            'leadSourceID' => null,
            'leadSource' => null,
            'leadStatus' => null,
            'leadStatusText' => null,
            'leadStageID' => null,
            'leadStage' => null,
            'leadAssignedTo' => null,
            'leadDateAssigned' => null,
            'leadValue' => null,
            'leadDateClosed' => null,
            'leadLostReason' => null,
            'leadLostReasonText' => null,
            'sourceID' => '1108',
            'source' => '*SALES REP',
            'annualRecurringServices' => '2',
            'regionID' => '0',
            'initialInvoice' => 'INITIAL_COMPLETION',
            'initialBillingDate' => '2024-04-12',
            'renewalFrequency' => '360',
            'renewalDate' => '0000-00-00',
            'customDate' => '0000-00-00',
            'sentriconConnected' => null,
            'sentriconSiteID' => null,
            'seasonalStart' => '0000-00-00',
            'seasonalEnd' => '0000-00-00',
            'nextBillingDate' => '2024-04-26',
            'maxMonthlyCharge' => '0.00',
            'expirationDate' => '0000-00-00',
            'lastAppointment' => '0',
            'templateType' => 'SERVICE_TYPE',
            'parentID' => '0',
            'duration' => '30',
            'preferredDays' => '',
            'preferredStart' => '00:00:00',
            'preferredEnd' => '00:00:00',
            'callAhead' => '0',
            'autopayPaymentProfileID' => '0',
            'billingTermsDays' => '0',
            'onHold' => '0',
            'customScheduleID' => '0',
            'unitIDs' => [],
            'recurringTicket' => null,
            'addOns' => [],
        ];
    }
}
