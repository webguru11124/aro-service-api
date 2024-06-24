<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Services\Vroom\DataTranslators\Transformers;

use App\Domain\RouteOptimization\Entities\WorkEvent\WorkBreak;
use App\Infrastructure\Services\Vroom\DataTranslators\Transformers\WorkBreakTransformer;
use Tests\TestCase;
use Tests\Tools\Factories\WorkBreakFactory;

class WorkBreakTransformerTest extends TestCase
{
    private WorkBreakTransformer $transformer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->transformer = new WorkBreakTransformer();
    }

    /**
     * @dataProvider maxLoadDataProvider
     *
     * @test
     */
    public function it_calculates_max_load_correctly(WorkBreak $workBreak, int $appointmentsNumber, int|null $expected): void
    {
        $vroomWorkBreak = $this->transformer->transform($workBreak, $appointmentsNumber);
        $result = $vroomWorkBreak->toArray();

        $this->assertEquals($expected, $result['max_load'][0] ?? null);
    }

    public static function maxLoadDataProvider(): iterable
    {
        /** @var WorkBreak $workBreak */
        $workBreak = WorkBreakFactory::make();

        yield [
            $workBreak->setMinAppointmentsBefore(7),
            15,
            8,
        ];
    }
}
