<?php

declare(strict_types=1);

namespace App\Application\Http\Responses;

use Illuminate\Http\JsonResponse;

abstract class AbstractResponse extends JsonResponse
{
    /**
     * @param int $status
     * @param array<string, string> $headers
     * @param int $options
     * @param bool $json
     */
    public function __construct(int $status = 500, array $headers = [], int $options = 0, bool $json = false)
    {
        parent::__construct($this->getResponseSchema(), $status, $headers, $options, $json);
    }

    protected function addMetadata(string $key, mixed $value): self
    {
        $data = $this->getData(true);
        $data['_metadata'][$key] = $value;
        $this->setData($data);

        return $this;
    }

    /**
     * @param array<string|int, mixed> $result
     *
     * @return self
     */
    protected function setResult(array $result): self
    {
        $data = $this->getData(true);
        $data['result'] = $result;
        $this->setData($data);

        return $this;
    }

    protected function setSuccess(bool $success): self
    {
        $data = $this->getData(true);
        $data['_metadata']['success'] = $success;
        $this->setData($data);

        return $this;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function getResponseSchema(): array
    {
        return [
            '_metadata' => ['success' => true],
            'result' => [],
        ];
    }
}
