<?php

declare(strict_types=1);

namespace Tests\Tools\Workday;

class StubServiceProReportResponse
{
    /**
     * Simulate a sample array service pro info response from Workday
     *
     * @return array
     */
    public static function responseWithSuccess(): array
    {
        return [
            'Report_Entry' => [
                [
                    'lastName' => 'Doe',
                    'HomeAddress' => '123 Main St',
                    'Email_-_Work' => 'johndoe@gmail.com',
                    'WorkAddressLine2' => 'Suite 200',
                    'PrimaryHomePhone' => '+1 (555) 123-4567',
                    'WorkAddPostal' => '12345',
                    'dateOfBirth' => '1980-01-01',
                    'HomeAddressCity' => 'Springfield',
                    'employmentStatus' => '1',
                    'Manager' => 'Jane Smith (ID123456)',
                    'scheduled_wk_hr' => 'Standard Work Schedule (M-F 9:00 AM - 5:00 PM) 1 hr lunch',
                    'HomeAddressState' => 'State',
                    'WorkAddressCity' => 'Capital City',
                    'firstName' => 'John',
                    'Skills' => 'Basic Training; Advanced Training',
                    'WorkdayID' => 'ABC123',
                    'businessTitle' => 'Senior Analyst',
                    'HomePostalCode' => '12345',
                    'FullWorkAddress' => "400 Capitol Mall\nSuite 200\nCapital City, State 12345",
                    'Worker' => 'John Doe (ID78910)',
                    'HireDate' => '2015-06-01',
                    'WorkAddStateISO' => 'ST',
                    'PreferredName' => 'John',
                    'WorkAddress' => '400 Capitol Mall',
                ],
            ],
        ];
    }
}
