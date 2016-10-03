<?php

namespace Amoscato\Bundle\AppBundle\Command;

use Amoscato\Bundle\AppBundle\Ftp\FtpClient;
use Amoscato\Bundle\AppBundle\Source\SourceCollection;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class LoadCurrentItemsCommand extends Command
{
    /** @var SourceCollection */
    private $sources;

    /** @var FtpClient */
    private $ftpClient;

    /**
     * @param SourceCollection $sources
     * @param FtpClient $ftpClient
     */
    public function __construct(SourceCollection $sources, FtpClient $ftpClient)
    {
        $this->sources = $sources;
        $this->ftpClient = $ftpClient;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('amoscato:current:load')
            ->setDescription('Loads current source data')
        ;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var \Amoscato\Console\Output\ConsoleOutput $output */

        $result = [];

        foreach ($this->sources as $type => $source) {
            $output->writeln("Loading {$type} source...");

            $result[$type] = $this->sources[$type]->load($output);
        }

        return $this->ftpClient->upload(
            $output,
            json_encode($result),
            'current.json'
        );
    }
}
