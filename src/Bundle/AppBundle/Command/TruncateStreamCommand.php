<?php

namespace Amoscato\Bundle\AppBundle\Command;

use Amoscato\Bundle\AppBundle\Stream\Query\StreamStatementProvider;
use Amoscato\Bundle\AppBundle\Stream\StreamAggregator;
use Amoscato\Database\PDOFactory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class TruncateStreamCommand extends Command
{
    /** @var PDOFactory */
    private $databaseFactory;

    /** @var \Amoscato\Bundle\AppBundle\Stream\Source\StreamSourceInterface[] */
    private $streamSources;

    /**
     * @param PDOFactory $pdoFactory
     * @param \Traversable $streamSources
     */
    public function __construct(PDOFactory $pdoFactory, \Traversable $streamSources)
    {
        parent::__construct();

        $this->databaseFactory = $pdoFactory;
        $this->streamSources = $streamSources;
    }

    protected function configure()
    {
        $this
            ->setName('amoscato:stream:truncate')
            ->setDescription('Truncates historic stream data')
            ->addOption(
                'size',
                null,
                InputOption::VALUE_REQUIRED,
                'Number of stream items to keep around',
                1.5 * StreamAggregator::DEFAULT_SIZE
            );
    }

    /**
     * {@inheritdoc}
     *
     * @param \Amoscato\Console\Output\ConsoleOutput $output
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $size = $input->getOption('size');
        $statementProvider = $this->getStreamStatementProvider();
        $weightedTypeHash = StreamAggregator::getWeightedTypeHash($this->streamSources);

        foreach ($this->streamSources as $source) {
            $type = $source->getType();

            $offset = StreamAggregator::getSourceLimit($weightedTypeHash, $size, $source) - 1;
            $output->writeVerbose("Selecting date of {$type} source #{$offset}");
            $createdAt = $statementProvider->selectCreatedDateAtOffset($type, $offset);

            if (false === $createdAt) {
                continue;
            }

            $output->writeln("Truncating {$type} sources before {$createdAt}");
            $statementProvider->deleteOldItems($type, $createdAt);
        }
    }

    /**
     * @return StreamStatementProvider
     */
    public function getStreamStatementProvider()
    {
        return new StreamStatementProvider($this->databaseFactory->getInstance());
    }
}
