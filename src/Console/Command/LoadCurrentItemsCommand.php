<?php

declare(strict_types=1);

namespace Amoscato\Console\Command;

use Amoscato\Console\Output\OutputDecorator;
use Amoscato\Source\Current\CurrentSourceInterface;
use GuzzleHttp\Utils;
use League\Flysystem\FilesystemOperator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Traversable;
use Webmozart\Assert\Assert;

class LoadCurrentItemsCommand extends Command
{
    private const STORAGE_LOCATION = 'current.json';

    /** @var CurrentSourceInterface[] */
    private $currentSources;

    /** @var FilesystemOperator */
    private $storage;

    public function __construct(FilesystemOperator $cacheStorage, Traversable $currentSources)
    {
        Assert::allIsInstanceOf($currentSources, CurrentSourceInterface::class);

        parent::__construct();

        $this->storage = $cacheStorage;
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
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output = OutputDecorator::create($output);

        $output->writeVerbose(sprintf('Reading old "%s"', self::STORAGE_LOCATION));
        $oldContents = $this->storage->read(self::STORAGE_LOCATION);

        $output->writeVerbose('Loading new contents');
        $newContents = $this->loadSourceContents($output);

        if ($oldContents !== $newContents) {
            $output->writeln(sprintf('Writing to "%s"', self::STORAGE_LOCATION));
            $this->storage->write(self::STORAGE_LOCATION, $newContents);
        }

        return 0;
    }

    private function loadSourceContents(OutputDecorator $output): string
    {
        $result = [];

        foreach ($this->currentSources as $source) {
            $type = $source->getType();

            $output->writeVeryVerbose(sprintf('Loading source "%s"', $type));
            $result[$type] = $source->load($output);
        }

        return Utils::jsonEncode($result);
    }
}
