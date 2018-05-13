<?php

namespace Amoscato\Bundle\AppBundle\Stream\Source;

use Amoscato\Bundle\AppBundle\Ftp\FtpClient;
use Amoscato\Bundle\IntegrationBundle\Client\VimeoClient;
use Amoscato\Console\Helper\PageIterator;
use Amoscato\Database\PDOFactory;
use Carbon\Carbon;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @property VimeoClient $client
 */
class VimeoSource extends AbstractStreamSource
{
    /**
     * @param PDOFactory $databaseFactory
     * @param FtpClient $ftpClient
     * @param VimeoClient $client
     */
    public function __construct(
        PDOFactory $databaseFactory,
        FtpClient $ftpClient,
        VimeoClient $client
    ) {
        parent::__construct($databaseFactory, $ftpClient, $client);
    }

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return 'vimeo';
    }

    /**
     * {@inheritdoc}
     */
    public function getPerPage()
    {
        return 50;
    }

    /**
     * @param int $perPage
     * @param PageIterator $iterator
     * @return array
     */
    protected function extract($perPage, PageIterator $iterator)
    {
        $response = $this->client->getLikes(
            [
                'page' => $iterator->current(),
                'per_page' => $perPage
            ]
        );

        if (!isset($response->paging->next)) {
            $iterator->setIsValid(false);
        }
        
        return $response->data;
    }

    /**
     * @param object $item
     * @param OutputInterface $output
     * @return array
     */
    protected function transform($item, OutputInterface $output)
    {
        $image = $item->pictures->sizes[2];

        return [
            $item->name,
            $item->link,
            Carbon::parse($item->metadata->interactions->like->added_time)->toDateTimeString(),
            $image->link,
            $image->width,
            $image->height
        ];
    }

    /**
     * @param object $item
     * @return string
     */
    protected function getSourceId($item)
    {
        return substr($item->uri, 8); // Remove "/videos/" prefix
    }
}
