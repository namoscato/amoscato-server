<?php

namespace Amoscato\Bundle\AppBundle\Stream\Source;

use Amoscato\Bundle\IntegrationBundle\Client\GitHubClient;
use Amoscato\Console\Helper\PageIterator;
use Carbon\Carbon;
use GuzzleHttp\Exception\ClientException;
use Symfony\Component\Console\Output\OutputInterface;

class GitHubSource extends AbstractSource
{
    /** @var int */
    protected $perPage = 30;

    /** @var string */
    protected $type = 'github';

    /** @var \Amoscato\Bundle\IntegrationBundle\Client\GitHubClient */
    protected $client;

    /** @var string */
    private $username;

    /**
     * @param int $perPage
     * @param PageIterator $iterator
     * @return array
     */
    protected function extract($perPage, PageIterator $iterator)
    {
        $page = $iterator->current();

        $response = $this->client->getUserEvents(
            $this->username,
            [
                'page' => $page
            ]
        );

        if ($page === GitHubClient::MAX_EVENT_PAGES) {
            $iterator->setIsValid(false);
        }

        return $response;
    }

    /**
     * @param object $item
     * @return array
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
                null
            ];
        } catch (ClientException $e) { // Gracefully handle client exceptions (i.e. 404)
            return false;
        }
    }

    /**
     * @param OutputInterface $output
     * @return bool
     */
    public function load(OutputInterface $output)
    {
        /** @var \Amoscato\Console\Output\ConsoleOutput $output */

        $iterator = new PageIterator(self::LIMIT);

        $commitHashes = [];
        $values = [];

        $latestSourceId = $this->getLatestSourceId();

        while ($iterator->valid()) {
            $items = $this->extract($this->perPage, $iterator);

            foreach ($items as $item) {
                if ($item->type !== GitHubClient::EVENT_TYPE_PUSH) {
                    continue;
                }

                $commitCount = count($item->payload->commits) - 1;

                for ($i = $commitCount; $i >= 0; $i--) { // Iterate through push event commits in decreasing chronological order
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

                    if ($transformedCommit === false) { // Skip select items
                        continue;
                    }

                    $values = array_merge(
                        [
                            $this->getType(),
                            $hash
                        ],
                        $transformedCommit,
                        $values
                    );

                    $output->writeVerbose("Transforming " . $this->getType() . " item: {$values[2]}");

                    $iterator->incrementCount();
                }
            }

            $iterator->next();
        }

        return $this->insertValues($output, $iterator->getCount(), $values);
    }

    /**
     * @param object $item
     * @return string
     */
    protected function getSourceId($item)
    {
        return $item->sha;
    }

    /**
     * @param string $username
     */
    public function setUsername($username)
    {
        $this->username = $username;
    }
}
