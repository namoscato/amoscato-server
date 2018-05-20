<?php

namespace Tests\Mocks\Bundle\AppBundle\Stream\Source;

use Amoscato\Bundle\AppBundle\Stream\Source\AbstractStreamSource;
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
