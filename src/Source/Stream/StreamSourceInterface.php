<?php

namespace Amoscato\Source\Stream;

interface StreamSourceInterface extends \Amoscato\Source\SourceInterface
{
    /**
     * @return int
     */
    public function getWeight();
}
