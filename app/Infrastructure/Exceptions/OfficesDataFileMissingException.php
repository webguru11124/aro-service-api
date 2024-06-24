<?php

declare(strict_types=1);

namespace App\Infrastructure\Exceptions;

class OfficesDataFileMissingException extends \Exception
{
    public function __construct()
    {
        parent::__construct(__('messages.static_office_repository.data_not_found'));
    }
}
