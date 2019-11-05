<?php

declare(strict_types=1);

namespace Amoscato\Console\Command;

use Amoscato\Console\Output\OutputDecorator;
use Amoscato\Ftp\FtpClient;
use Amoscato\Source\Current\CurrentSourceInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Traversable;
use Webmozart\Assert\Assert;

class LoadCurrentItemsCommand extends Command
{
    /** @var CurrentSourceInterface[] */
    private $currentSources;

    /** @var FtpClient */
    private $ftpClient;

    public function __construct(FtpClient $ftpClient, Traversable $currentSources)
    {
        Assert::allIsInstanceOf($currentSources, CurrentSourceInterface::class);

        parent::__construct();

        $this->ftpClient = $ftpClient;
        $this->currentSources = $currentSources;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
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
        $output = OutputDecorator::create($output);
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
