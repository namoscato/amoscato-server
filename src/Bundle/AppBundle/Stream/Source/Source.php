<?php

namespace Amoscato\Bundle\AppBundle\Stream\Source;

abstract class Source implements SourceInterface
{
    /**
     * @var string
     */
    protected $type;

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }
}
