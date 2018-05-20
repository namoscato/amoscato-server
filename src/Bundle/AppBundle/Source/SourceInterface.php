<?php

namespace Amoscato\Bundle\AppBundle\Source;

use Amoscato\Console\Output\ConsoleOutput;

interface SourceInterface
{
    /**
     * @return string
     */
    public function getType();

    /**
     * @param ConsoleOutput $output
     * @param int $limit
     * @return array
     */
    public function load(ConsoleOutput $output, $limit = 1);
}
