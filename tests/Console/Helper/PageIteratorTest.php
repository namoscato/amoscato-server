<?php

declare(strict_types=1);

namespace Tests\Console\Helper;

use Amoscato\Console\Helper\PageIterator;
use PHPUnit\Framework\TestCase;

class PageIteratorTest extends TestCase
{
    public function test_limit(): void
    {
        $iterator = new PageIterator(3);

        $this->assertSame(1, $iterator->current());
        $this->assertSame(1, $iterator->key());
        $this->assertSame(true, $iterator->valid());
        $this->assertSame(0, $iterator->getCount());

        $iterator->incrementCount();
        $this->assertSame(1, $iterator->getCount());

        $iterator->incrementCount();
        $this->assertSame(2, $iterator->getCount());

        $iterator->next();

        $this->assertSame(2, $iterator->current());
        $this->assertSame(2, $iterator->key());
        $this->assertSame(true, $iterator->valid());
        $this->assertSame(2, $iterator->getCount());

        $iterator->incrementCount();
        $this->assertSame(3, $iterator->getCount());

        $this->assertSame(false, $iterator->valid());
    }

    public function test_valid(): void
    {
        $iterator = new PageIterator(3);

        $iterator->incrementCount();
        $iterator->next();

        $this->assertSame(true, $iterator->valid());

        $iterator->next();

        $this->assertSame(false, $iterator->valid());
    }

    public function test_valid_empty(): void
    {
        $iterator = new PageIterator(3);

        $iterator->next();

        $this->assertSame(false, $iterator->valid());
    }

    public function test_setNextPageValue(): void
    {
        $iterator = new PageIterator(3);

        $iterator->setNextPageValue('page2');

        $this->assertSame(1, $iterator->current());

        $iterator->incrementCount();
        $iterator->next();

        $this->assertSame('page2', $iterator->current());
    }

    public function test_rewind(): void
    {
        $iterator = new PageIterator(3);

        $iterator->incrementCount();
        $iterator->next();
        $iterator->incrementCount();
        $iterator->next();

        $this->assertSame(3, $iterator->key());
        $this->assertSame(3, $iterator->current());

        $iterator->rewind();

        $this->assertSame(1, $iterator->key());
        $this->assertSame(1, $iterator->current());
    }
}
