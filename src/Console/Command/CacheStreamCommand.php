<?php

declare(strict_types=1);

namespace Amoscato\Console\Command;

use Amoscato\Ftp\FtpClient;
use Amoscato\Source\Stream\StreamAggregator;
use GuzzleHttp\Utils;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CacheStreamCommand extends Command
{
    /** @var StreamAggregator */
    private $streamAggregator;

    /** @var FtpClient */
    private $ftpClient;

    public function __construct(StreamAggregator $streamAggregator, FtpClient $ftpClient)
    {
        parent::__construct();

        $this->streamAggregator = $streamAggregator;
        $this->ftpClient = $ftpClient;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->setName('amoscato:stream:cache')
            ->setDescription('Caches the stream data via FTP')
            ->addOption(
                'size',
                null,
                InputOption::VALUE_REQUIRED,
                'Number of stream items to cache',
                StreamAggregator::DEFAULT_SIZE
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->ftpClient->upload(
            $output,
            Utils::jsonEncode($this->streamAggregator->aggregate((float) $input->getOption('size'))),
            'stream.json'
        );

        return 0;
    }
}
