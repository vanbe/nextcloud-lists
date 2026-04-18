<?php

declare(strict_types=1);

namespace OCA\Lists\Exception;

use Exception;

class ForbiddenException extends Exception {
    public function __construct(string $message = 'Access denied') {
        parent::__construct($message);
    }
}
