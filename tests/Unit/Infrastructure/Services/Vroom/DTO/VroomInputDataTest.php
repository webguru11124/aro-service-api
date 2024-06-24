<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Services\Vroom\DTO;

use App\Infrastructure\Services\Vroom\DTO\Job;
use App\Infrastructure\Services\Vroom\DTO\Vehicle;
use App\Infrastructure\Services\Vroom\DTO\VroomEngineOption;
use App\Infrastructure\Services\Vroom\DTO\VroomInputData;
use Illuminate\Support\Collection;
use Tests\TestCase;
use Tests\Tools\Factories\Vroom\JobFactory;
use Tests\Tools\Factories\Vroom\VehicleFactory;

class VroomInputDataTest extends TestCase
{
    private VroomInputData $vroomInputData;
    /** @var Collection<Vehicle> */
    private Collection $vehicles;
    /** @var Collection<Job>  */
    private Collection $jobs;
    /** @var Collection<VroomEngineOption>  */
    private Collection $options;

    protected function setUp(): void
    {
        parent::setUp();

        $this->vehicles = new Collection(VehicleFactory::many(2));
        $this->jobs = new Collection(JobFactory::many(4));
        $this->options = new Collection([
            VroomEngineOption::CHOOSE_ETA,
            VroomEngineOption::GEOMETRY,
        ]);
        $this->vroomInputData = new VroomInputData(
            $this->vehicles,
            $this->jobs,
            $this->options,
        );
    }

    /**
     * @test
     */
    public function it_returns_vehicles(): void
    {
        $this->assertSame($this->vehicles, $this->vroomInputData->getVehicles());
    }

    /**
     * @test
     */
    public function it_returns_jobs(): void
    {
        $this->assertSame($this->jobs, $this->vroomInputData->getJobs());
    }

    /**
     * @test
     */
    public function it_returns_options(): void
    {
        $this->assertSame($this->options, $this->vroomInputData->getOptions());
    }

    /**
     * @test
     */
    public function it_transforms_vehicles_to_array(): void
    {
        $array = $this->vroomInputData->toArray();

        $this->assertNotEmpty($array['vehicles']);
        $this->assertCount($this->vehicles->count(), $array['vehicles']);

        $actualIds = array_map(fn (array $vehicleArray) => $vehicleArray['id'], $array['vehicles']);
        $vehiclesIds = $this->vehicles->map(fn (Vehicle $vehicle) => $vehicle->getId())->all();

        $this->assertEquals($vehiclesIds, $actualIds);
    }

    /**
     * @test
     */
    public function it_transforms_jobs_to_array(): void
    {
        $array = $this->vroomInputData->toArray();

        $this->assertNotEmpty($array['jobs']);
        $this->assertCount($this->jobs->count(), $array['jobs']);

        $actualIds = array_map(fn (array $jobsArray) => $jobsArray['id'], $array['jobs']);
        $jobsIds = $this->jobs->map(fn (Job $job) => $job->getId())->all();

        $this->assertEquals($jobsIds, $actualIds);
    }

    /**
     * @test
     */
    public function it_provides_a_flag_to_show_distance(): void
    {
        $array = $this->vroomInputData->toArray();

        $this->assertTrue($array['options']['g']);
    }
}
