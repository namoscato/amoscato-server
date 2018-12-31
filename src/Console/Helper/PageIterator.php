<?php

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
     * Return the current element
     *
     * @return mixed Can return any type.
     */
    public function current()
    {
        if (isset($this->pageValues[$this->pageIndex])) {
            return $this->pageValues[$this->pageIndex];
        }

        return $this->pageIndex;
    }

    /**
     * Move forward to next element
     *
     * @return void Any returned value is ignored.
     */
    public function next()
    {
        $this->pageIndex++;

        if ($this->previousCount === $this->count) {
            $this->isValid = false;
        }

        $this->previousCount = $this->count;
    }

    /**
     * Return the key of the current element
     *
     * @return mixed scalar on success, or null on failure.
     */
    public function key()
    {
        return $this->pageIndex;
    }

    /**
     * Checks if current position is valid
     *
     * @return boolean Returns true on success or false on failure.
     */
    public function valid()
    {
        return $this->isValid && $this->count < $this->limit;
    }

    /**
     * Rewind the Iterator to the first element
     *
     * @return void Any returned value is ignored.
     */
    public function rewind()
    {
        $this->pageIndex = 1;
    }

    /**
     * Increments the internal count
     *
     * @return void
     */
    public function incrementCount()
    {
        $this->count++;
    }

    /**
     * Returns the internal count
     *
     * @return int
     */
    public function getCount()
    {
        return $this->count;
    }

    /**
     * Sets the next page value
     *
     * @param mixed $value
     */
    public function setNextPageValue($value)
    {
        $this->pageValues[$this->pageIndex + 1] = $value;
    }

    /**
     * Sets the valid property
     *
     * @param bool $isValid
     */
    public function setIsValid($isValid)
    {
        $this->isValid = $isValid;
    }
}
