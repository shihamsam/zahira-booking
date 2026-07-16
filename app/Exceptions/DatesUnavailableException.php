<?php

namespace App\Exceptions;

use Exception;

class DatesUnavailableException extends Exception
{
    /**
     * @param array<int, string> $unavailableDates
     */
    public function __construct(protected array $unavailableDates)
    {
        parent::__construct(
            'The following date(s) are no longer available: '.implode(', ', $unavailableDates)
        );
    }

    public function getUnavailableDates(): array
    {
        return $this->unavailableDates;
    }
}
