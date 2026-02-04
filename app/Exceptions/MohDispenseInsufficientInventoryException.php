<?php

namespace App\Exceptions;

use Exception;

class MohDispenseInsufficientInventoryException extends Exception
{
    /** @var array<int, array<string, mixed>> */
    protected array $insufficientItems;

    /**
     * @param array<int, array<string, mixed>> $insufficientItems
     */
    public function __construct(string $message = '', array $insufficientItems = [], int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->insufficientItems = $insufficientItems;
    }

    /** @return array<int, array<string, mixed>> */
    public function getInsufficientItems(): array
    {
        return $this->insufficientItems;
    }
}
