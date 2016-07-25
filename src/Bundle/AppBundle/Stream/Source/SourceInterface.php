<?php

namespace Amoscato\Bundle\AppBundle\Stream\Source;

use Symfony\Component\Console\Output\OutputInterface;

interface SourceInterface
{
    /**
     * @return string
     */
    public function getType();

    /**
     * @return int
     */
    public function getWeight();

    /**
     * @param OutputInterface $output
     * @return bool
     */
    public function load(OutputInterface $output);
}
