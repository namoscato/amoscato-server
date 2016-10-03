<?php

namespace Tests\Mocks\Bundle\AppBundle\Stream\Source;

use Amoscato\Bundle\AppBundle\Stream\Source\AbstractSource;
use Amoscato\Console\Helper\PageIterator;

class MockSource extends AbstractSource
{
    protected $type = 'mockType';

    protected function extract($perPage, PageIterator $iterator)
    {
        return $this->mockExtract($perPage, $iterator->current());
    }

    protected function transform($item)
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
