<?php

namespace Amoscato\Bundle\AppBundle\Stream\Source;

interface StreamSourceInterface extends \Amoscato\Bundle\AppBundle\Source\SourceInterface
{
    /**
     * @return int
     */
    public function getWeight();
}