<?php

namespace Tests\Console\Helper;

use Amoscato\Console\Helper\PageIterator;

class PageIteratorTest extends \PHPUnit_Framework_TestCase
{
    public function test_limit()
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

    public function test_valid()
    {
        $iterator = new PageIterator(3);

        $iterator->incrementCount();
        $iterator->next();

        $this->assertSame(true, $iterator->valid());

        $iterator->next();

        $this->assertSame(false, $iterator->valid());
    }

    public function test_valid_empty()
    {
        $iterator = new PageIterator(3);

        $iterator->next();

        $this->assertSame(false, $iterator->valid());
    }

    public function test_setNextPageValue()
    {
        $iterator = new PageIterator(3);

        $iterator->setNextPageValue('page2');

        $this->assertSame(1, $iterator->current());

        $iterator->incrementCount();
        $iterator->next();

        $this->assertSame('page2', $iterator->current());
    }

    public function test_rewind()
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
