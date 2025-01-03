<?php

declare(strict_types=1);

namespace Amoscato\Console\Command;

use Amoscato\Console\Error\ErrorOutput;
use Amoscato\Console\Error\GitHubActionsErrorOutput;
use Amoscato\Console\Output\OutputDecorator;
use Amoscato\Source\SourceInterface;
use Amoscato\Source\Stream\StreamSourceInterface;
use GuzzleHttp\Exception\ServerException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Webmozart\Assert\Assert;

class LoadStreamItemsCommand extends Command
{
    /** @var StreamSourceInterface[] */
    private array $streamSources = [];

    public function __construct(\Traversable $streamSources)
    {
        Assert::allIsInstanceOf($streamSources, StreamSourceInterface::class);

        parent::__construct();

        foreach ($streamSources as $streamSource) {
            /* @var SourceInterface $streamSource */
            $this->streamSources[$streamSource->getType()] = $streamSource;
        }
    }

    protected function configure(): void
    {
        $this
            ->setName('amoscato:stream:load')
            ->setDescription('Loads stream data')
            ->addArgument(
                'sources',
                InputArgument::IS_ARRAY | InputArgument::OPTIONAL,
                'Optional set of sources to load'
            )
            ->addOption(
                'limit',
                null,
                InputOption::VALUE_REQUIRED,
                'Number of items to load',
                100
            )
            ->addOption(
                'github',
                null,
                InputOption::VALUE_NONE,
                'Assume GitHub Actions execution context',
            );
    }

    /**
     * @throws InvalidArgumentException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output = OutputDecorator::create($output);
        $errorOutput = $input->getOption('github') ? new GitHubActionsErrorOutput($output) : new ErrorOutput($output);

        $sources = $input->getArgument('sources');

        foreach ($sources as $type) { // Validate arguments
            if (!isset($this->streamSources[$type])) {
                throw new InvalidArgumentException("Source type '$type' is undefined");
            }
        }

        if (empty($sources)) {
            $sources = &$this->streamSources;
        }

        $limit = (int) $input->getOption('limit');
        $fatalExceptionCount = 0;

        foreach ($sources as $type => $source) {
            try {
                if (!$source instanceof StreamSourceInterface) {
                    $source = $this->streamSources[$source];
                    $type = $source->getType();
                }

                $output->writeln("Extracting $limit $type source...");
                $source->load($output, $limit);
            } catch (ServerException $serverException) {
                $errorOutput->writeln($serverException->getMessage());
            } catch (\Throwable $throwable) {
                $errorOutput->writeln($throwable->getMessage());
                ++$fatalExceptionCount;
            }
        }

        if ($fatalExceptionCount) {
            throw new RuntimeException("$fatalExceptionCount stream loading errors");
        }

        return Command::SUCCESS;
    }
}
