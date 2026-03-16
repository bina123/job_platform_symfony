<?php

namespace App\Exception;

class ResourceNotFoundException extends ApiException
{
    public function __construct(string $resource = 'Resource', int|string $id = '')
    {
        parent::__construct(sprintf('%s not found (id: %s)', $resource, $id), 404);
    }
}
