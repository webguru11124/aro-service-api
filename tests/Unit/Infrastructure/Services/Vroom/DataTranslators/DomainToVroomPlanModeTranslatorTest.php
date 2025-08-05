<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Services\Vroom\DataTranslators;

use App\Infrastructure\Services\Vroom\DataTranslators\DomainToVroomPlanModeTranslator;
use App\Infrastructure\Services\Vroom\DataTranslators\Transformers\AppointmentTransformer;
use App\Infrastructure\Services\Vroom\DataTranslators\Transformers\CoordinateTransformer;
use App\Infrastructure\Services\Vroom\DataTranslators\Transformers\MeetingTransformer;
use App\Infrastructure\Services\Vroom\DataTranslators\Transformers\SkillTransformer;
use App\Infrastructure\Services\Vroom\DataTranslators\Transformers\TimeWindowTransformer;
use App\Infrastructure\Services\Vroom\DataTranslators\Transformers\WorkBreakTransformer;
use Tests\TestCase;
use Tests\Tools\Factories\OfficeFactory;
use Tests\Traits\AssertArrayHasAllKeys;
use Tests\Traits\VroomTranslatorDataProvider;
use App\Infrastructure\Services\Vroom\DataTranslators\Transformers\ReservedTimeTransformer;

class DomainToVroomPlanModeTranslatorTest extends TestCase
{
    use VroomTranslatorDataProvider;
    use AssertArrayHasAllKeys;

    private DomainToVroomPlanModeTranslator $translator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->translator = new DomainToVroomPlanModeTranslator(
            new AppointmentTransformer(),
            new MeetingTransformer(),
            new SkillTransformer(),
            new WorkBreakTransformer(),
            new TimeWindowTransformer(),
            new CoordinateTransformer(),
            new ReservedTimeTransformer(),
        );
        $this->office = OfficeFactory::make();
    }

    /**
     * @test
     */
    public function it_translates_optimization_state_to_vroom_input_data(): void
    {
        $optimizationState = $this->getIncomingOptimizationState();
        $actual = $this->translator->translate($optimizationState);
        $this->assertArrayHasAllKeys($this->vroomRequest(), $actual->toArray());
    }

    private function vroomRequest(): array
    {
        return [
            'vehicles' => [
                [
                    'id',
                    'description',
                    'end',
                    'skills',
                    'start',
                    'time_window',
                    'breaks' => [
                        [
                            'id',
                            'description',
                            'service',
                            'time_windows',
                        ],
                    ],
                    'steps' => [
                        [
                            'id',
                            'type',
                        ],
                    ],
                    'capacity',
                    'speed_factor',
                ],
            ],
            'jobs' => [
                [
                    'id',
                    'time_windows',
                    'skills',
                    'service',
                    'location',
                    'description',
                    'priority',
                    'delivery',
                    'setup',
                ],
            ],
            'options' => [
                'g',
            ],
        ];
    }
}
