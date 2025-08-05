<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Calendar\ValueObjects;

use App\Domain\Calendar\Enums\EndAfter;
use App\Domain\Calendar\Exceptions\InvalidEventEnd;
use App\Domain\Calendar\ValueObjects\EventEnd;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Tests\TestCase;

class EventEndTest extends TestCase
{
    /**
     * @test
     */
    public function it_can_be_created_with_end_after_date(): void
    {
        $date = Carbon::now()->addMonth();
        $eventEnd = new EventEnd(
            endAfter: EndAfter::DATE,
            date: $date
        );

        $this->assertTrue($eventEnd->isDate());
        $this->assertFalse($eventEnd->isOccurrences());
        $this->assertFalse($eventEnd->isNever());
        $this->assertSame($date, $eventEnd->getDate());
        $this->assertEquals(EndAfter::DATE, $eventEnd->getEndAfter());
    }

    /**
     * @test
     */
    public function it_can_be_created_with_end_after_occurrences(): void
    {
        $occurrences = $this->faker->randomNumber(2);
        $eventEnd = new EventEnd(
            endAfter: EndAfter::OCCURRENCES,
            occurrences: $occurrences
        );

        $this->assertTrue($eventEnd->isOccurrences());
        $this->assertFalse($eventEnd->isDate());
        $this->assertFalse($eventEnd->isNever());
        $this->assertSame($occurrences, $eventEnd->getOccurrences());
        $this->assertEquals(EndAfter::OCCURRENCES, $eventEnd->getEndAfter());
    }

    /**
     * @test
     *
     * @dataProvider invalidDataProvider
     */
    public function it_throws_an_exception_on_invalid_data(
        EndAfter $endAfter,
        CarbonInterface|null $date,
        int|null $occurrences
    ): void {
        $this->expectException(InvalidEventEnd::class);

        new EventEnd($endAfter, $date, $occurrences);
    }

    public static function invalidDataProvider(): iterable
    {
        yield [
            EndAfter::DATE,
            null,
            random_int(3, 10),
        ];

        yield [
            EndAfter::OCCURRENCES,
            Carbon::now()->addMonth(),
            null,
        ];
    }
}
