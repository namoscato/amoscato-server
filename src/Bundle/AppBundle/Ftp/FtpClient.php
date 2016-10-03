<?php

namespace Amoscato\Bundle\AppBundle\Ftp;

use RuntimeException;
use Symfony\Component\Console\Output\OutputInterface;

class FtpClient
{
    /** @var string */
    private $ftpHost;

    /** @var string */
    private $ftpUser;

    /** @var string */
    private $ftpPassword;

    /**
     * @param string $ftpHost
     * @param string $ftpUser
     * @param string $ftpPassword
     */
    public function __construct($ftpHost, $ftpUser, $ftpPassword)
    {
        $this->ftpHost = $ftpHost;
        $this->ftpUser = $ftpUser;
        $this->ftpPassword = $ftpPassword;
    }

    /**
     * @param OutputInterface $output
     * @param string $data
     * @param string $fileName
     * @return int
     */
    public function upload(OutputInterface $output, $data, $fileName)
    {
        /** @var \Amoscato\Console\Output\ConsoleOutput $output */

        if (!$connectionId = ftp_connect($this->ftpHost)) {
            throw new RuntimeException('Unable to connect to FTP server.');
        }

        $output->writeVerbose('Logging into FTP stream...');

        if (!ftp_login($connectionId, $this->ftpUser, $this->ftpPassword)) {
            throw new RuntimeException('Unable to connect to FTP server.');
        }

        $output->writeVerbose('Creating file...');

        if (!$filePath = tempnam(sys_get_temp_dir(), 'stream')) {
            throw new RuntimeException('Unable to create temporary file.');
        }

        $output->writeVerbose(sprintf('Writing to file %s...', $filePath));

        if (!$handle = fopen($filePath, 'w')) {
            throw new RuntimeException('Error opening file.');
        }

        if (!fwrite($handle, $data)) {
            throw new RuntimeException('Error writing to file.');
        }

        if (!fclose($handle)) {
            throw new RuntimeException('Error closing file.');
        }

        $output->writeVerbose('Enabling passive mode...');

        if (!ftp_pasv($connectionId, true)) {
            throw new RuntimeException('Unable to enable passive mode.');
        }

        $output->writeVerbose('Uploading file...');

        if (!ftp_put($connectionId, $fileName, $filePath, FTP_BINARY)) {
            throw new RuntimeException('Error uploading file.');
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
