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
        $username
    ) {
        parent::__construct($databaseFactory, $client);

        $this->username = $username;
    }

    /**
     * {@inheritdoc}
     */
    public function getType(): string
    {
        return 'github';
    }

    /**
     * {@inheritdoc}
     */
    protected function getMaxPerPage(): int
    {
        return 30;
    }

    /**
     * {@inheritdoc}
     */
    protected function extract($perPage, PageIterator $iterator): array
    {
        $page = $iterator->current();

        $response = $this->client->getUserEvents(
            $this->username,
            [
                'page' => $page,
            ]
        );

        if (GitHubClient::MAX_EVENT_PAGES === $page) {
            $iterator->setIsValid(false);
        }

        return $response;
    }

    /**
     * {@inheritdoc}
     */
    protected function transform($item)
    {
        try {
            $response = $this->client->getCommit($item->url);

            if ($response->author->login !== $this->username) {
                return false;
            }

            return [
                explode("\n", $item->message)[0],
                $response->html_url,
                Carbon::parse($response->commit->author->date)->toDateTimeString(),
                null,
                null,
                null,
            ];
        } catch (ClientException $e) { // Gracefully handle client exceptions (i.e. 404)
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function load(OutputInterface $output, int $limit = 1): bool
    {
        $output = OutputDecorator::create($output);

        $iterator = new PageIterator($limit);

        $commitHashes = [];
        $values = [];

        $latestSourceId = $this->getLatestSourceId();

        while ($iterator->valid()) {
            $items = $this->extract($this->getPerPage($limit), $iterator);

            foreach ($items as $item) {
                if (GitHubClient::EVENT_TYPE_PUSH !== $item->type) {
                    continue;
                }

                $commitCount = count($item->payload->commits) - 1;

                for ($i = $commitCount; $i >= 0; --$i) { // Iterate through push event commits in decreasing chronological order
                    $hash = $this->getSourceId($item->payload->commits[$i]);

                    if (isset($commitHashes[$hash])) { // Skip duplicate commits
                        continue;
                    }

                    $commitHashes[$hash] = true;

                    if ($latestSourceId === $hash) { // Break if item is already in database
                        $output->writeDebug("Item {$latestSourceId} is already in the database");
                        break 3;
                    }

                    $transformedCommit = $this->transform($item->payload->commits[$i]);

                    if (false === $transformedCommit) { // Skip select items
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

    /**
     * {@inheritdoc}
     */
    protected function getSourceId($item): string
    {
        return $item->sha;
    }
}
