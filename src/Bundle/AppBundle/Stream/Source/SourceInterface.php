<?php

namespace Amoscato\Bundle\AppBundle\Stream\Source;

interface SourceInterface
{
    public function getType();
    public function load();
}
