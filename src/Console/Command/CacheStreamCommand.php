<?php

declare(strict_types=1);

namespace Amoscato\Console\Command;

use Amoscato\Source\Stream\StreamAggregator;
use GuzzleHttp\Utils;
use League\Flysystem\FilesystemOperator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CacheStreamCommand extends Command
{
    /** @var StreamAggregator */
    private $streamAggregator;

    /** @var FilesystemOperator */
    private $storage;

    public function __construct(StreamAggregator $streamAggregator, FilesystemOperator $cacheStorage)
    {
        parent::__construct();

        $this->streamAggregator = $streamAggregator;
        $this->storage = $cacheStorage;
    }

    protected function configure(): void
    {
        $this
            ->setName('amoscato:stream:cache')
            ->setDescription('Caches the stream data')
            ->addOption(
                'size',
                null,
                InputOption::VALUE_REQUIRED,
                'Number of stream items to cache',
                StreamAggregator::DEFAULT_SIZE
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $stream = $this->streamAggregator->aggregate((float) $input->getOption('size'));

        $this->storage->write('stream.json', Utils::jsonEncode($stream));

        return 0;
    }
}
