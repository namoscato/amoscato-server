<?php

declare(strict_types=1);

namespace Amoscato\Ftp;

use Amoscato\Console\Output\OutputDecorator;
use Exception;
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

    /** @var string */
    private $ftpDirectory;

    /**
     * @param string $ftpHost
     * @param string $ftpUser
     * @param string $ftpPassword
     * @param string $ftpDirectory
     */
    public function __construct($ftpHost, $ftpUser, $ftpPassword, $ftpDirectory = '')
    {
        $this->ftpHost = $ftpHost;
        $this->ftpUser = $ftpUser;
        $this->ftpPassword = $ftpPassword;
        $this->ftpDirectory = $ftpDirectory;
    }

    /**
     * @param string $data
     * @param string $fileName
     * @param string $directory
     *
     * @return string Remote path of uploaded file
     */
    public function upload(OutputInterface $output, $data, $fileName, $directory = null): string
    {
        $output = OutputDecorator::create($output);

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

        if (!$handle = fopen($filePath, 'wb')) {
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

        $result = $this->ftpDirectory;

        if (isset($directory)) {
            try {
                $output->writeVerbose("Changing to directory {$directory}...");

                ftp_chdir($connectionId, $directory);
            } catch (Exception $e) {
                $output->writeVerbose("Creating directory {$directory}...");

                if (!ftp_mkdir($connectionId, $directory)) {
                    throw new RuntimeException('Unable to create directory.');
                }

                $output->writeVerbose("Changing to directory {$directory}...");

                if (!ftp_chdir($connectionId, $directory)) {
                    throw new RuntimeException('Unable to change directory.');
                }
            }

            $result .= "/{$directory}";
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

        return "{$result}/{$fileName}";
    }
}
