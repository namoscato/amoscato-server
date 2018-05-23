<?php

namespace Amoscato\Bundle\AppBundle\Command;

use Amoscato\Bundle\AppBundle\Ftp\FtpClient;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class LoadCurrentItemsCommand extends Command
{
    /** @var \Amoscato\Bundle\AppBundle\Source\SourceInterface[] */
    private $currentSources;

    /** @var FtpClient */
    private $ftpClient;

    /**
     * @param FtpClient $ftpClient
     * @param \Traversable $currentSources
     */
    public function __construct(FtpClient $ftpClient, \Traversable $currentSources)
    {
        parent::__construct();

        $this->ftpClient = $ftpClient;
        $this->currentSources = $currentSources;
    }

    protected function configure()
    {
        $this
            ->setName('amoscato:current:load')
            ->setDescription('Loads current source data');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var \Amoscato\Console\Output\ConsoleOutput $output */

        $result = [];

        foreach ($this->currentSources as $source) {
            $type = $source->getType();
            $output->writeln("Loading {$type} source...");
            $result[$type] = $source->load($output);
        }

        if ('dev' === $input->getOption('env')) {
            $output->writeln(var_export($result, true));
        } else {
            $this->ftpClient->upload($output, json_encode($result), 'current.json');
        }
    }
}
