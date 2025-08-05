<?php

declare(strict_types=1);

namespace App\Infrastructure\Services\Workday\Queries;

use App\Domain\Contracts\Queries\EmployeeInfoQuery;
use App\Domain\SharedKernel\Entities\Employee;
use App\Domain\SharedKernel\Entities\Skill;
use App\Domain\SharedKernel\Entities\WorkPeriod;
use App\Domain\SharedKernel\ValueObjects\Address;
use App\Infrastructure\Services\Workday\Exceptions\WorkdayErrorException;
use App\Infrastructure\Services\Workday\WorkdayAPIClient;
use App\Domain\Contracts\Queries\Params\EmployeeInfoQueryParams;
use Illuminate\Support\Collection;
use Throwable;

class WorkdayEmployeeInfoQuery implements EmployeeInfoQuery
{
    private const WORKDAY_REPORT_URL = 'workday.services.service_pro_info_report_url';
    private const PRO_FIELDS_MAP = [
        'employeeId' => 'WorkdayID',
        'firstName' => 'firstName',
        'lastName' => 'lastName',
        'dateOfBirth' => 'dateOfBirth',
        'dateOfHire' => 'HireDate',
        'managerId' => 'Manager',
        'email' => 'Email_-_Work',
        'phone' => 'PrimaryHomePhone',
        'address' => 'HomeAddress',
        'city' => 'HomeAddressCity',
        'state' => 'HomeAddressState',
        'zip' => 'HomePostalCode',
    ];

    public function __construct(
        private WorkdayAPIClient $workdayAPIClient
    ) {
    }

    /**
     * @param EmployeeInfoQueryParams $params
     *
     * @return Collection<Employee>
     * @throws WorkdayErrorException
     */
    public function get(EmployeeInfoQueryParams $params): Collection
    {
        try {
            $response = $this->workdayAPIClient->get(config(self::WORKDAY_REPORT_URL), $params->toArray());

            return $this->buildReportEntries($response);
        } catch (Throwable $ex) {
            throw new WorkdayErrorException(__('messages.workday.error_fetching_report', [
                'error' => $ex->getMessage(),
            ]));
        }
    }

    /**
     * @param array<string, mixed> $report
     *
     * @return Collection<Employee>
     * @throws WorkdayErrorException
     */
    private function buildReportEntries(array $report): Collection
    {
        try {
            $entries = $this->extractReportEntries($report);

            return collect(array_map([$this, 'buildEntry'], $entries));
        } catch (Throwable $ex) {
            throw new WorkdayErrorException(__('messages.workday.error_parsing_response', [
                'error' => $ex->getMessage(),
            ]));
        }
    }

    /**
     * @param array<string, mixed> $result
     *
     * @return array<string, mixed>
     */
    private function extractReportEntries(array $result): array
    {
        return $result['Report_Entry'] ?? [];
    }

    /**
     * @param array<string, mixed> $entry
     *
     * @return Employee
     */
    private function buildEntry(array $entry): Employee
    {
        $item = $this->buildItem($entry);
        $skills = $this->extractSkills($entry['Skills'] ?? '');
        $workPeriod = new WorkPeriod($entry['scheduled_wk_hr'] ?? '');

        return new Employee(
            $item['employeeId'],
            $item['firstName'],
            $item['lastName'],
            $item['dateOfBirth'],
            $item['dateOfHire'],
            $item['managerId'],
            $item['email'],
            $item['phone'],
            new Address($item['address'], $item['city'], $item['state'], $item['zip']),
            $workPeriod,
            $skills
        );
    }

    /**
     * @param array<string, mixed> $entry
     *
     * @return array<string, mixed>
     */
    private function buildItem(array $entry): array
    {
        $item = [];

        foreach (self::PRO_FIELDS_MAP as $key => $path) {
            $item[$key] = $entry[$path] ?? null;
        }

        return $item;
    }

    /**
     * @param string $skillsRaw
     *
     * @return Collection<Skill>
     */
    private function extractSkills(string $skillsRaw): Collection
    {
        $skillList = explode('; ', $skillsRaw);

        return collect(array_map(fn ($skill) => new Skill($skill), $skillList));
    }
}
