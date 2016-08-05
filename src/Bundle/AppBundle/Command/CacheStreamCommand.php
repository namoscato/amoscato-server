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

        $connectionId = ftp_connect($this->ftpHost);

        if (!$connectionId) {
            throw new RuntimeException('Unable to connect to FTP server.');
        }

        $output->writeVerbose('Logging into FTP stream...');

        $result = ftp_login($connectionId, $this->ftpUser, $this->ftpPassword);

        if (!$result) {
            throw new RuntimeException('Unable to connect to FTP server.');
        }

        $output->writeVerbose('Enabling passive mode...');

        $result = ftp_pasv($connectionId, true);

        if (!$result) {
            throw new RuntimeException('Unable to enable passive mode.');
        }

        $output->writeVerbose('Aggregating stream items...');

        $streamData = $this->streamAggregator->aggregate();

        $output->writeVerbose('Writing to cache file...');

        $filePath = tempnam(sys_get_temp_dir(), 'stream');

        $handle = fopen($filePath, 'w');
        fwrite($handle, json_encode($streamData));
        fclose($handle);

        $output->writeVerbose('Uploading cache file...');

        $upload = ftp_put($connectionId, 'stream.json', $filePath, FTP_BINARY);

        if (!$upload) {
            throw new RuntimeException('Error uploading cache file.');
        }

        unlink($filePath);
        ftp_close($connectionId);

        return 0;
    }
}
