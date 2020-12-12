<?php

declare(strict_types=1);

namespace Tests\Console\Helper;

use Amoscato\Console\Helper\PageIterator;
use PHPUnit\Framework\TestCase;

class PageIteratorTest extends TestCase
{
    public function testLimit(): void
    {
        $iterator = new PageIterator(3);

        self::assertSame(1, $iterator->current());
        self::assertSame(1, $iterator->key());
        self::assertTrue($iterator->valid());
        self::assertSame(0, $iterator->getCount());

        $iterator->incrementCount();
        self::assertSame(1, $iterator->getCount());

        $iterator->incrementCount();
        self::assertSame(2, $iterator->getCount());

        $iterator->next();

        self::assertSame(2, $iterator->current());
        self::assertSame(2, $iterator->key());
        self::assertTrue($iterator->valid());
        self::assertSame(2, $iterator->getCount());

        $iterator->incrementCount();
        self::assertSame(3, $iterator->getCount());

        self::assertFalse($iterator->valid());
    }

    public function testValid(): void
    {
        $iterator = new PageIterator(3);

        $iterator->incrementCount();
        $iterator->next();

        self::assertTrue($iterator->valid());

        $iterator->next();

        self::assertFalse($iterator->valid());
    }

    public function testValidEmpty(): void
    {
        $iterator = new PageIterator(3);

        $iterator->next();

        self::assertFalse($iterator->valid());
    }

    public function testSetNextPageValue(): void
    {
        $iterator = new PageIterator(3);

        $iterator->setNextPageValue('page2');

        self::assertSame(1, $iterator->current());

        $iterator->incrementCount();
        $iterator->next();

        self::assertSame('page2', $iterator->current());
    }

    public function testRewind(): void
    {
        $iterator = new PageIterator(3);

        $iterator->incrementCount();
        $iterator->next();
        $iterator->incrementCount();
        $iterator->next();

        self::assertSame(3, $iterator->key());
        self::assertSame(3, $iterator->current());

        $iterator->rewind();

        self::assertSame(1, $iterator->key());
        self::assertSame(1, $iterator->current());
    }
}
