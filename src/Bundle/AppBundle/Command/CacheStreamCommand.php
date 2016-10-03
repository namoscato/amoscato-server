<?php

namespace Amoscato\Bundle\AppBundle\Command;

use Amoscato\Bundle\AppBundle\Ftp\FtpClient;
use Amoscato\Bundle\AppBundle\Stream\StreamAggregator;
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

    /**
     * @param StreamAggregator $streamAggregator
     * @param FtpClient $ftpClient
     */
    public function __construct(StreamAggregator $streamAggregator, FtpClient $ftpClient)
    {
        $this->streamAggregator = $streamAggregator;
        $this->ftpClient = $ftpClient;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('amoscato:stream:cache')
            ->setDescription('Caches the stream data via FTP')
            ->addOption(
                'size',
                null,
                InputOption::VALUE_REQUIRED,
                'Number of stream items to cache',
                1000.0
            )
        ;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return integer
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        return $this->ftpClient->upload(
            $output,
            json_encode(
                $this->streamAggregator->aggregate(
                    floatval($input->getOption('size'))
                )
            ),
            'stream.json'
        );
    }
}
