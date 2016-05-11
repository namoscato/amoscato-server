<?php

namespace Tests\Mocks\Bundle\AppBundle\Stream\Source;

use Amoscato\Bundle\AppBundle\Stream\Source\Source;

class MockSource extends Source
{
    protected $type = 'mockType';

    protected function extract($perPage = self::LIMIT, $page = 1)
    {
        return $this->mockExtract($perPage, $page);
    }

    protected function transform($item)
    {
        return $this->mockTransform($item);
    }

    public function mockTransform($item)
    {
        return null;
    }

    public function mockExtract($limit, $page)
    {
        return null;
    }
}
