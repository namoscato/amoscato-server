<?php

namespace Amoscato\Bundle\AppBundle\Command;

use Amoscato\Bundle\AppBundle\Stream\Source\StreamSourceInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class LoadStreamItemsCommand extends Command
{
    /** @var \Amoscato\Bundle\AppBundle\Source\SourceInterface[] */
    private $streamSources = [];

    /**
     * @param \Traversable $streamSources
     */
    public function __construct(\Traversable $streamSources)
    {
        parent::__construct();

        foreach ($streamSources as $streamSource) {
            /** @var \Amoscato\Bundle\AppBundle\Source\SourceInterface $streamSource */
            $this->streamSources[$streamSource->getType()] = $streamSource;
        }
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
            ->addOption(
                'limit',
                null,
                InputOption::VALUE_REQUIRED,
                'Number of items to load',
                100
            );
    }

    /**
     * {@inheritdoc}
     *
     * @param \Amoscato\Console\Output\ConsoleOutput $output
     * @throws InvalidArgumentException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $sources = $input->getArgument('sources');

        foreach ($sources as $type) { // Validate arguments
            if (!isset($this->streamSources[$type])) {
                throw new InvalidArgumentException("Source type '{$type}' is undefined");
            }
        }

        if (empty($sources)) {
            $sources = &$this->streamSources;
        }

        $limit = $input->getOption('limit');

        foreach ($sources as $type => $source) {
            if (!$source instanceof StreamSourceInterface) {
                $source = $this->streamSources[$source];
                $type = $source->getType();
            }

            $output->writeln("Extracting {$limit} {$type} source...");
            $source->load($output, $limit);
        }
    }
}
