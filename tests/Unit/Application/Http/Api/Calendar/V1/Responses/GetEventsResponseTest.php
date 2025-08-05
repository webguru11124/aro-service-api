<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Http\Api\Calendar\V1\Responses;

use App\Application\Http\Api\Calendar\V1\Responses\GetEventsResponse;
use App\Domain\Calendar\Entities\RecurringEvent;
use App\Domain\Calendar\Enums\ScheduleInterval;
use App\Domain\Calendar\Enums\WeekDay;
use App\Domain\Calendar\ValueObjects\WeeklyOccurrence;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Tests\TestCase;
use Tests\Tools\Factories\Calendar\OverrideFactory;
use Tests\Tools\Factories\Calendar\RecurringEventFactory;
use Tests\Traits\AssertArrayHasAllKeys;

class GetEventsResponseTest extends TestCase
{
    use AssertArrayHasAllKeys;

    /**
     * @test
     */
    public function it_creates_correct_response(): void
    {
        $override = OverrideFactory::make([
            'date' => Carbon::parse('2024-01-01'),
        ]);

        /** @var RecurringEvent $event1 */
        $event1 = RecurringEventFactory::make([
            'startDate' => Carbon::parse('2024-01-01'),
            'date' => Carbon::parse('2024-01-01'),
            'interval' => ScheduleInterval::WEEKLY,
            'weeklyOccurrence' => new WeeklyOccurrence(collect([WeekDay::MONDAY])),
            'overrides' => collect([$override]),
        ]);
        /** @var RecurringEvent $event2 */
        $event2 = RecurringEventFactory::make([
            'startDate' => Carbon::parse('2024-01-01'),
            'date' => Carbon::parse('2024-01-08'),
            'interval' => ScheduleInterval::WEEKLY,
            'weeklyOccurrence' => new WeeklyOccurrence(collect([WeekDay::MONDAY])),
            'overrides' => collect([$override]),
        ]);

        $page = 1;
        $perPage = 10;

        $response = new GetEventsResponse(collect([$event1, $event2]), $page, $perPage);
        $responseData = $response->getData(true);

        $this->assertArrayHasAllKeys([
            'result' => [
                'events' => [
                    '2024-01-01' => [
                        '0' => [
                            'id',
                            'office_id',
                            'start_date',
                            'end_date',
                            'interval',
                            'week_days',
                            'week_num',
                            'service_pro_ids',
                            'title',
                            'description',
                            'start_at',
                            'end_at',
                            'time_zone',
                            'location' => [
                                'lat',
                                'lng',
                            ],
                            'override_id',
                            'is_canceled',
                        ],
                    ],
                    '2024-01-08' => [
                        '0' => [
                            'id',
                            'office_id',
                            'start_date',
                            'end_date',
                            'interval',
                            'week_days',
                            'week_num',
                            'service_pro_ids',
                            'title',
                            'description',
                            'start_at',
                            'end_at',
                            'time_zone',
                            'location' => [
                                'lat',
                                'lng',
                            ],
                            'override_id',
                            'is_canceled',
                        ],
                    ],
                ],
            ],
        ], $responseData);
    }

    /**
     * @test
     *
     * @dataProvider paginationDataProvider
     */
    public function it_paginates_correctly(int $page, int $perPage, int $totalPages, array $expectedKeys): void
    {
        $events = new Collection();
        $totalItems = 5;
        $date = Carbon::parse('2024-01-01');

        for ($i = 1; $i <= $totalItems; $i++) {
            $events->add(RecurringEventFactory::make([
                'startDate' => Carbon::parse('2024-01-01'),
                'endDate' => Carbon::parse('2024-01-31'),
                'date' => $date->clone(),
                'interval' => ScheduleInterval::WEEKLY,
                'weeklyOccurrence' => new WeeklyOccurrence(collect([WeekDay::MONDAY])),
            ]));
            $date->addDays(7);
        }

        $response = new GetEventsResponse($events, $page, $perPage);
        $responseData = $response->getData(true);
        $this->assertArrayHasAllKeys($expectedKeys, $responseData);

        $expectedCount = count($expectedKeys['result']['events']);
        $this->assertCount($expectedCount, $responseData['result']['events']);
        $this->assertSame($totalItems, $responseData['_metadata']['pagination']['total']);
        $this->assertSame($page, $responseData['_metadata']['pagination']['current_page']);
        $this->assertSame($perPage, $responseData['_metadata']['pagination']['per_page']);
        $this->assertSame($totalPages, $responseData['_metadata']['pagination']['total_pages']);
    }

    public static function paginationDataProvider(): iterable
    {
        yield [
            'page' => 1,
            'perPage' => 5,
            'totalPages' => 1,
            [
                'result' => [
                    'events' => [
                        '2024-01-01',
                        '2024-01-08',
                        '2024-01-15',
                        '2024-01-22',
                        '2024-01-29',
                    ],
                ],
            ],
        ];

        yield [
            'page' => 2,
            'perPage' => 2,
            'totalPages' => 3,
            [
                'result' => [
                    'events' => [
                        '2024-01-15',
                        '2024-01-22',
                    ],
                ],
            ],
        ];

        yield [
            'page' => 3,
            'perPage' => 2,
            'totalPages' => 3,
            [
                'result' => [
                    'events' => [
                        '2024-01-29',
                    ],
                ],
            ],
        ];
    }
}
