<?php

declare(strict_types=1);

namespace Amoscato\Console\Command;

use Amoscato\Console\Output\OutputDecorator;
use Amoscato\Database\PDOFactory;
use Amoscato\Source\Stream\Query\StreamStatementProvider;
use Amoscato\Source\Stream\StreamAggregator;
use Amoscato\Source\Stream\StreamSourceInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Webmozart\Assert\Assert;

class TruncateStreamCommand extends Command
{
    /** @var PDOFactory */
    private $databaseFactory;

    /** @var StreamSourceInterface[] */
    private $streamSources;

    public function __construct(PDOFactory $pdoFactory, \Traversable $streamSources)
    {
        Assert::allIsInstanceOf($streamSources, StreamSourceInterface::class);

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

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output = OutputDecorator::create($output);
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

        return 0;
    }

    public function getStreamStatementProvider(): StreamStatementProvider
    {
        return new StreamStatementProvider($this->databaseFactory->getInstance());
    }
}
