<?php

namespace Tests\Mocks\Source\Stream;

use Amoscato\Source\Stream\AbstractStreamSource;
use Amoscato\Console\Helper\PageIterator;
use Symfony\Component\Console\Output\OutputInterface;

class MockSource extends AbstractStreamSource
{
    public function getType()
    {
        return 'mockType';
    }

    protected function getMaxPerPage()
    {
        return 100;
    }

    protected function extract($perPage, PageIterator $iterator)
    {
        return $this->mockExtract($perPage, $iterator->current());
    }

    protected function transform($item, OutputInterface $output)
    {
        return $this->mockTransform($item);
    }

    public function mockTransform($item)
    {
        return null;
    }

    public function mockExtract($limit, $iterator)
    {
        return null;
    }
}
