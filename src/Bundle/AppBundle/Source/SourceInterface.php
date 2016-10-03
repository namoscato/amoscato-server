<?php

namespace Amoscato\Bundle\AppBundle\Source;

use Symfony\Component\Console\Output\OutputInterface;

interface SourceInterface
{
    /**
     * @return string
     */
    public function getType();

    /**
     * @param OutputInterface $output
     * @return array
     */
    public function load(OutputInterface $output);
}
