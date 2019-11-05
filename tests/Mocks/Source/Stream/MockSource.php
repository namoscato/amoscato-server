<?php

declare(strict_types=1);

namespace Tests\Mocks\Source\Stream;

use Amoscato\Console\Helper\PageIterator;
use Amoscato\Source\Stream\AbstractStreamSource;

class MockSource extends AbstractStreamSource
{
    public function getType(): string
    {
        return 'mockType';
    }

    protected function getMaxPerPage(): int
    {
        return 100;
    }

    protected function extract($perPage, PageIterator $iterator): iterable
    {
        return $this->mockExtract($perPage, $iterator->current());
    }

    protected function transform($item)
    {
        return $this->mockTransform($item);
    }

    public function mockTransform($item)
    {
        return $item;
    }

    public function mockExtract($limit, $iterator): array
    {
        return [];
    }
}
