<?php

declare(strict_types=1);

namespace Tests\Tools;

use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

abstract class AbstractRequestTest extends TestCase
{
    protected array $rules = [];

    /**
     * Instance of tested request
     *
     * @return Request
     */
    abstract public function getTestedRequest(): Request;

    /**
     * Data provider for negative cases
     *
     * @return iterable
     */
    abstract public static function getInvalidData(): iterable;

    /**
     * Data provider for positive cases
     *
     * @return iterable
     */
    abstract public static function getValidData(): iterable;

    protected function setUp(): void
    {
        parent::setUp();
        /** @phpstan-ignore-next-line */
        $this->rules = $this->getTestedRequest()->rules();
    }

    /** @test
     *
     * @dataProvider getInvalidData
     */
    public function it_should_fail_validation_if_incorrect_data_is_provided($data): void
    {
        $validator = $this->makeValidator($data, $this->rules);

        $this->assertFalse($validator->passes());
    }

    /** @test
     *
     * @dataProvider getValidData
     */
    public function it_should_pass_validation_if_correct_data_is_provided(array $data): void
    {
        $validator = $this->makeValidator($data, $this->rules);

        $this->assertTrue($validator->passes());
    }

    protected function makeValidator(array $data, array $rules): ValidatorContract
    {
        return Validator::make($data, $rules);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->rules);
    }
}
