<?php

declare(strict_types=1);

namespace App\Domain\RouteOptimization\ValueObjects;

class Skill
{
    public const INITIAL_SERVICE = 1001;
    public const AA = 1039;
    public const AL = 1036;
    public const AK = 1034;
    public const AR = 1038;
    public const AZ = 1003;
    public const CA = 1004;
    public const CO = 1005;
    public const DE = 1043;
    public const FL = 1006;
    public const GA = 1007;
    public const IA = 1008;
    public const ID = 1009;
    public const IL = 1010;
    public const IN = 1011;
    public const KS = 1012;
    public const KY = 1013;
    public const MA = 1014;
    public const MD = 1015;
    public const MI = 1016;
    public const MN = 1017;
    public const MO = 1018;
    public const MS = 1035;
    public const MT = 1037;
    public const NC = 1019;
    public const NE = 1020;
    public const NJ = 1021;
    public const NV = 1022;
    public const NY = 1023;
    public const OH = 1024;
    public const OK = 1025;
    public const OR = 1026;
    public const PA = 1027;
    public const RI = 1041;
    public const SC = 1042;
    public const TN = 1028;
    public const TX = 1029;
    public const UT = 1002;
    public const VA = 1031;
    public const WA = 1032;
    public const WI = 1033;
    public const INI = self::INITIAL_SERVICE; // need this to have possibility to convert 'INI' string to Skill back
    private const UNKNOWN_SKILL = 'unknown';
    private const INITIAL_SHORT = 'INI';

    private const SERVICE_PRO_PERSONAL_SKILL_MULTIPLIER = 1000;

    public function __construct(public readonly int $value)
    {
    }

    /**
     * @param string $state
     *
     * @return self
     */
    public static function fromState(string $state): self
    {
        $reflection = new \ReflectionClass(self::class);
        $skill = $reflection->getConstant($state);

        if ($skill === false) {
            throw new \ValueError("$state is not a valid skill.");
        }

        return new self($skill);
    }

    /**
     * @param string $state
     *
     * @return self|null
     */
    public static function tryFromState(string $state): self|null
    {
        try {
            return self::fromState($state);
        } catch (\ValueError) {
            return null;
        }
    }

    /**
     * @return string
     */
    public function getLiteral(): string
    {
        $reflection = new \ReflectionClass(self::class);

        $constants = $reflection->getConstants();

        $literal = array_search($this->value, $constants);

        if ($literal === 'INITIAL_SERVICE') {
            return self::INITIAL_SHORT;
        }

        if ($literal === false) {
            return self::UNKNOWN_SKILL;
        }

        return $literal;
    }

    /**
     * @param int $id
     */
    public static function createPersonalSkillFromId(int $id): self
    {
        return new self($id * self::SERVICE_PRO_PERSONAL_SKILL_MULTIPLIER);
    }
}
