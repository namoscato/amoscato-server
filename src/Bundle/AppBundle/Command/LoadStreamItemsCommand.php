<?php

namespace Amoscato\Bundle\AppBundle\Command;

use Amoscato\Bundle\AppBundle\Source\SourceCollection;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class LoadStreamItemsCommand extends Command
{
    /** @var SourceCollection */
    private $sources;

    /**
     * @param SourceCollection $sources
     */
    public function __construct(SourceCollection $sources)
    {
        $this->sources = $sources;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('amoscato:stream:load')
            ->setDescription('Loads stream data')
            ->addArgument(
                'sources',
                InputArgument::IS_ARRAY | InputArgument::OPTIONAL,
                'Optional set of sources to load'
            )
        ;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return integer
     * @throws InvalidArgumentException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $sources = $input->getArgument('sources');

        foreach ($sources as $type) { // Validate arguments
            if (!isset($this->sources[$type])) {
                throw new InvalidArgumentException("Source type '{$type}' is undefined");
            }
        }

        if (empty($sources)) {
            foreach ($this->sources as $type => $source) {
                $this->loadSource($output, $type);
            }
        } else {
            foreach ($sources as $type) {
                $this->loadSource($output, $type);
            }
        }
        
        return 0;
    }

    /**
     * @param OutputInterface $output
     * @param string $type
     */
    private function loadSource(OutputInterface $output, $type)
    {
        /** @var \Amoscato\Console\Output\ConsoleOutput $output */

        $output->writeln("Extracting {$type} source...");

        $this->sources[$type]->load($output);
    }
}
