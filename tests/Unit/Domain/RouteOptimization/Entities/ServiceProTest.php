<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\RouteOptimization\Entities;

use App\Domain\RouteOptimization\Entities\ServicePro;
use App\Domain\RouteOptimization\ValueObjects\Skill;
use App\Domain\SharedKernel\ValueObjects\Coordinate;
use App\Domain\SharedKernel\ValueObjects\TimeWindow;
use Carbon\Carbon;
use Tests\TestCase;
use Tests\Tools\Factories\ServiceProFactory;
use Tests\Traits\DomainDataAndObjects;

class ServiceProTest extends TestCase
{
    use DomainDataAndObjects;

    /**
     * @test
     */
    public function create_service_pro(): void
    {
        $startLocation = new Coordinate(self::LOCATION_START_LATITUDE, self::LOCATION_START_LONGITUDE);
        $endLocation = new Coordinate(self::LOCATION_END_LATITUDE, self::LOCATION_END_LONGITUDE);

        $workingHours = new TimeWindow(
            Carbon::createFromTimestamp(self::START_OF_WORKDAY_TIMESTAMP),
            Carbon::createFromTimestamp(self::END_OF_WORKDAY_TIMESTAMP),
        );

        $skills = [
            new Skill(Skill::AA),
            new Skill(Skill::INITIAL_SERVICE),
        ];
        $link = $this->faker->text(7);
        $avatarBase64 = $this->faker->text(7);

        $servicePro = new ServicePro(
            self::SERVICE_PRO_ID,
            self::SERVICE_PRO_NAME,
            $startLocation,
            $endLocation,
            $workingHours,
            $link,
            $avatarBase64,
        );

        $servicePro->addSkills($skills);

        $assertedSkills = array_merge([$servicePro->getPersonalSkill()], $skills);

        $this->assertEquals(self::SERVICE_PRO_NAME, $servicePro->getName());
        $this->assertSame($startLocation, $servicePro->getStartLocation());
        $this->assertSame($endLocation, $servicePro->getEndLocation());
        $this->assertEquals($assertedSkills, $servicePro->getSkills()->all());
        $this->assertEquals($workingHours, $servicePro->getWorkingHours());
        $this->assertSame($link, $servicePro->getWorkdayId());
        $this->assertSame($avatarBase64, $servicePro->getAvatarBase64());
    }

    /**
     * @test
     */
    public function it_returns_skills_with_personal(): void
    {
        $skills = [new Skill(Skill::AA)];

        /** @var ServicePro $servicePro */
        $servicePro = ServiceProFactory::make([
            'skills' => $skills,
        ]);

        $result = $servicePro->getSkills()->values();
        $expected = array_merge([$servicePro->getPersonalSkill()], $skills);

        $this->assertEquals($expected, $result->all());
    }

    /**
     * @test
     */
    public function it_correctly_adds_skills_to_service_pro(): void
    {
        $servicePro = ServiceProFactory::make();

        $initialSkills = $servicePro->getSkills()->toArray();

        $skillsToAdd = [
            new Skill(Skill::AK),
            new Skill(Skill::INITIAL_SERVICE),
        ];
        $servicePro->addSkills($skillsToAdd);

        $addedSkills = $servicePro->getSkills()->values()->all();
        $expectedSkills = array_merge($initialSkills, $skillsToAdd);

        $this->assertEquals($expectedSkills, $addedSkills);
    }

    /**
     * @test
     */
    public function it_does_not_add_duplicate_skills(): void
    {
        $skill1 = new Skill(Skill::AA);
        $skill2 = new Skill(Skill::INITIAL_SERVICE);
        $initialSkills = [$skill1, $skill2];

        $servicePro = ServiceProFactory::make([
            'skills' => $initialSkills,
        ]);

        $servicePro->addSkills($initialSkills);

        $expectedSkills = array_merge([$servicePro->getPersonalSkill()], $initialSkills);
        $this->assertEquals($expectedSkills, $servicePro->getSkills()->all());

        $skill3 = new Skill(Skill::OK);
        $servicePro->addSkills([$skill1, $skill3]);

        $expectedSkills = array_merge([$servicePro->getPersonalSkill()], $initialSkills, [$skill3]);
        $this->assertEquals($expectedSkills, $servicePro->getSkills()->all());
    }

    /**
     * @test
     */
    public function it_returns_skills_without_personal(): void
    {
        $skills = [new Skill(Skill::AA)];

        /** @var ServicePro $servicePro */
        $servicePro = ServiceProFactory::make([
            'skills' => $skills,
        ]);

        $result = $servicePro->getSkillsWithoutPersonal()->values();

        $expected = $skills;

        $this->assertEquals($expected, $result->all());
    }
}
