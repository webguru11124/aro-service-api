<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Http\Web\SchedulingOverview\Requests;

use Illuminate\Http\Request;
use Tests\Tools\AbstractRequestTest;
use App\Application\Http\Web\SchedulingOverview\Requests\SchedulingExecutionsRequest;

class SchedulingExecutionsRequestTest extends AbstractRequestTest
{
    public function getTestedRequest(): Request
    {
        return new SchedulingExecutionsRequest();
    }

    public static function getInvalidData(): iterable
    {
        return [
            'execution_date is not a date' => [['execution_date' => 'not-a-date']],
            'execution_date is not in Y-m-d format' => [['execution_date' => '2021-01-01 00:00:00']],
        ];
    }

    public static function getValidData(): iterable
    {
        return [
            'execution_date is a date' => [['execution_date' => '2021-01-01']],
        ];
    }
}
