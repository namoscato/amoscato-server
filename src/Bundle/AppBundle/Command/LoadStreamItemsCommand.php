<?php

namespace Amoscato\Bundle\AppBundle\Command;

use Amoscato\Bundle\AppBundle\Stream\Source\SourceInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class LoadStreamItemsCommand extends Command
{
    private $sources;

    public function __construct()
    {
        $this->sources = [];

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('amoscato:stream:load')
            ->setDescription('Loads stream data')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        foreach ($this->sources as $type => $source) {
            $source->load();
        }
    }
    
    public function pushSource(SourceInterface $source)
    {
        $this->sources[$source->getType()] = $source;
    }
}
