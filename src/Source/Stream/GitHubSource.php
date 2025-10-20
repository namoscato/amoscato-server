<?php

declare(strict_types=1);

namespace Amoscato\Source\Stream;

use Amoscato\Console\Helper\PageIterator;
use Amoscato\Console\Output\OutputDecorator;
use Amoscato\Database\PDOFactory;
use Amoscato\Integration\Client\GitHubClient;
use Carbon\Carbon;
use GuzzleHttp\Exception\ClientException;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @property GitHubClient $client
 */
class GitHubSource extends AbstractStreamSource
{
    /** @var string */
    private $username;

    /**
     * @param string $username
     */
    public function __construct(
        PDOFactory $databaseFactory,
        GitHubClient $client,
        $username,
    ) {
        parent::__construct($databaseFactory, $client);

        $this->username = $username;
    }

    public function getType(): string
    {
        return 'github';
    }

    protected function getMaxPerPage(): int
    {
        return 100;
    }

    protected function extract($perPage, PageIterator $iterator): array
    {
        $page = $iterator->current();

        $response = $this->client->getUserEvents(
            $this->username,
            [
                'page' => $page,
            ]
        );

        return $response;
    }

    protected function transform($item)
    {
        try {
            if ($item->author->login !== $this->username) {
                return false;
            }

            return [
                explode("\n", $item->commit->message)[0],
                $item->html_url,
                Carbon::parse($item->commit->author->date)->toDateTimeString(),
                null,
                null,
                null,
            ];
        } catch (ClientException $e) { // Gracefully handle client exceptions (i.e. 404)
            return false;
        }
    }

    public function load(OutputInterface $output, int $limit = 1): bool
    {
        $output = OutputDecorator::create($output);

        $iterator = new PageIterator($limit);

        $commitHashes = [];
        $values = [];

        $latestSourceId = $this->getLatestSourceId();

        while ($iterator->valid()) {
            $output->writeDebug("Fetching page {$iterator->current()}");
            $items = $this->extract($this->getPerPage($limit), $iterator);

            foreach ($items as $item) {
                if (GitHubClient::EVENT_TYPE_PUSH !== $item->type) {
                    $output->writeDebug("Item {$item->id}: Skipping non-push event");
                    continue;
                }

                // Extract repo owner and name from repo URL
                [$owner, $repo] = explode('/', $item->repo->name);

                $basehead = "{$item->payload->before}...{$item->payload->head}";
                $output->writeDebug("Item {$item->id}: Fetching commits {$basehead}");
                $commits = $this->client->compareCommits($owner, $repo, $basehead)->commits;

                if (empty($commits)) {
                    $output->writeDebug("Item {$item->id}: No commits found");
                    continue;
                }

                for ($i = count($commits) - 1; $i >= 0; --$i) { // Iterate through push event commits in reverse chronological order
                    $hash = $this->getSourceId($commits[$i]);

                    if (isset($commitHashes[$hash])) { // Skip duplicate commits
                        $output->writeDebug("Item {$item->id}: Skipping duplicate commit {$hash}");
                        continue;
                    }

                    $commitHashes[$hash] = true;

                    if ($latestSourceId === $hash) { // Break if item is already in database
                        $output->writeDebug("Item {$item->id}: Commit {$hash} already in database");
                        break 3;
                    }

                    $transformedCommit = $this->transform($commits[$i]);

                    if (false === $transformedCommit) { // Skip select items
                        $output->writeDebug("Item {$item->id}: Skipping commit {$hash}");
                        continue;
                    }

                    /** @noinspection SlowArrayOperationsInLoopInspection */
                    $values = array_merge(
                        [
                            $this->getType(),
                            $hash,
                        ],
                        $transformedCommit,
                        $values
                    );

                    $output->writeVerbose("Transforming {$this->getType()} item: {$values[2]}");

                    $iterator->incrementCount();
                }
            }

            $iterator->next();
        }

        return $this->insertValues($output, $iterator->getCount(), $values);
    }

    protected function getSourceId($item): string
    {
        return $item->sha;
    }
}
