<?php

declare(strict_types=1);

namespace Tests\Integration\Application\Http\Rules;

use Tests\TestCase;
use App\Application\Http\Rules\NativeBoolean;

class NativeBooleanTest extends TestCase
{
    private NativeBoolean $rule;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->rule = new NativeBoolean();
    }

    /**
     * @return array
     */
    public static function validationDataProvider(): array
    {
        return [
            'null' => [null, false],
            'string' => ['string', false],
            'int' => [1, false],
            'float' => [1.1, false],
            'array' => [[], false],
            'object' => [(object) [], false],
            'boolean true' => [true, true],
            'boolean false' => [false, true],
        ];
    }

    /**
     * @test
     *
     * @dataProvider validationDataProvider
     *
     * @param mixed $value
     * @param bool $expected
     *
     * @return void
     */
    public function it_validates_boolean_value(mixed $value, bool $expected): void
    {
        $validationResult = true;
        $this->rule->validate('attribute', $value, function () use (&$validationResult) {
            $validationResult = false;
        });

        $this->assertEquals($expected, $validationResult);
    }
}
