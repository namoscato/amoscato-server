<?php

namespace Amoscato\Bundle\AppBundle\Stream\Source;

use Amoscato\Bundle\AppBundle\Ftp\FtpClient;
use Amoscato\Bundle\IntegrationBundle\Client\GitHubClient;
use Amoscato\Console\Helper\PageIterator;
use Amoscato\Database\PDOFactory;
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
     * @param PDOFactory $databaseFactory
     * @param FtpClient $ftpClient
     * @param GitHubClient $client
     * @param string $username
     */
    public function __construct(
        PDOFactory $databaseFactory,
        FtpClient $ftpClient,
        GitHubClient $client,
        $username
    ) {
        parent::__construct($databaseFactory, $ftpClient, $client);

        $this->username = $username;
    }

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return 'github';
    }

    /**
     * {@inheritdoc}
     */
    public function getPerPage()
    {
        return 30;
    }

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
     * {@inheritdoc}
     */
    protected function transform($item, OutputInterface $output)
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
            $items = $this->extract($this->getPerPage(), $iterator);

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

                    $transformedCommit = $this->transform($item->payload->commits[$i], $output);

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

                    $output->writeVerbose("Transforming {$this->getType()} item: {$values[2]}");

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
}
