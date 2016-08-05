<?php

namespace Amoscato\Bundle\AppBundle\Command;

use Amoscato\Bundle\AppBundle\Stream\Source\SourceCollection;
use Amoscato\Bundle\AppBundle\Stream\StreamAggregator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Exception\InvalidOptionException;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CacheStreamCommand extends Command
{
    /** @var StreamAggregator */
    private $streamAggregator;

    /** @var string */
    private $ftpHost;

    /** @var string */
    private $ftpUser;

    /** @var string */
    private $ftpPassword;

    /**
     * @param StreamAggregator $streamAggregator
     * @param string $ftpHost
     * @param string $ftpUser
     * @param string $ftpPassword
     */
    public function __construct(StreamAggregator $streamAggregator, $ftpHost, $ftpUser, $ftpPassword)
    {
        $this->streamAggregator = $streamAggregator;
        $this->ftpHost = $ftpHost;
        $this->ftpUser = $ftpUser;
        $this->ftpPassword = $ftpPassword;

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
     * @throws InvalidArgumentException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var \Amoscato\Console\Output\ConsoleOutput $output */

        $output->writeVerbose('Connecting to FTP server...');

        if (!$connectionId = ftp_connect($this->ftpHost)) {
            throw new RuntimeException('Unable to connect to FTP server.');
        }

        $output->writeVerbose('Logging into FTP stream...');

        if (!ftp_login($connectionId, $this->ftpUser, $this->ftpPassword)) {
            throw new RuntimeException('Unable to connect to FTP server.');
        }

        $output->writeVerbose('Aggregating stream items...');

        $streamData = $this->streamAggregator->aggregate(
            floatval($input->getOption('size'))
        );

        if (!$filePath = tempnam(sys_get_temp_dir(), 'stream')) {
            throw new RuntimeException('Unable to create temporary file.');
        }

        $output->writeVerbose(sprintf('Writing to cache file %s...', $filePath));

        if (!$handle = fopen($filePath, 'w')) {
            throw new RuntimeException('Error opening file.');
        }

        if (!fwrite($handle, json_encode($streamData))) {
            throw new RuntimeException('Error writing to file.');
        }

        if (!fclose($handle)) {
            throw new RuntimeException('Error closing file.');
        }

        $output->writeVerbose('Enabling passive mode...');

        if (!ftp_pasv($connectionId, true)) {
            throw new RuntimeException('Unable to enable passive mode.');
        }

        $output->writeVerbose('Uploading cache file...');

        if (!ftp_put($connectionId, 'stream.json', $filePath, FTP_BINARY)) {
            throw new RuntimeException('Error uploading cache file.');
        }

        if (!ftp_close($connectionId)) {
            throw new RuntimeException('Error closing FTP connection.');
        }

        if (!unlink($filePath)) {
            throw new RuntimeException('Error unlinking file.');
        }

        return 0;
    }
}
