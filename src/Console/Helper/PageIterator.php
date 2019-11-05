<?php

declare(strict_types=1);

namespace Amoscato\Console\Helper;

use Iterator;

/**
 * Pagination helper that keeps track of an internal count
 * that represents the aggregate number of items across
 * multiple pages.
 */
class PageIterator implements Iterator
{
    /** @var int */
    private $count = 0;

    /** @var int */
    private $limit;

    /** @var int */
    private $pageIndex = 1;

    /** @var int */
    private $previousCount = 0;

    /** @var bool */
    private $isValid = true;

    /** @var array */
    private $pageValues = [];

    /**
     * @param int $limit Count limit
     */
    public function __construct($limit)
    {
        $this->limit = $limit;
    }

    /**
     * Return the current element.
     *
     * @return mixed can return any type
     */
    public function current()
    {
        return $this->pageValues[$this->pageIndex] ?? $this->pageIndex;
    }

    /**
     * Move forward to next element.
     */
    public function next(): void
    {
        ++$this->pageIndex;

        if ($this->previousCount === $this->count) {
            $this->isValid = false;
        }

        $this->previousCount = $this->count;
    }

    /**
     * Return the key of the current element.
     *
     * @return mixed scalar on success, or null on failure
     */
    public function key()
    {
        return $this->pageIndex;
    }

    /**
     * Checks if current position is valid.
     *
     * @return bool returns true on success or false on failure
     */
    public function valid(): bool
    {
        return $this->isValid && $this->count < $this->limit;
    }

    /**
     * Rewind the Iterator to the first element.
     */
    public function rewind(): void
    {
        $this->pageIndex = 1;
    }

    /**
     * Increments the internal count.
     */
    public function incrementCount(): void
    {
        ++$this->count;
    }

    /**
     * Returns the internal count.
     */
    public function getCount(): int
    {
        return $this->count;
    }

    /**
     * Sets the next page value.
     *
     * @param mixed $value
     */
    public function setNextPageValue($value): void
    {
        $this->pageValues[$this->pageIndex + 1] = $value;
    }

    /**
     * Sets the valid property.
     *
     * @param bool $isValid
     */
    public function setIsValid($isValid): void
    {
        $this->isValid = $isValid;
    }
}
