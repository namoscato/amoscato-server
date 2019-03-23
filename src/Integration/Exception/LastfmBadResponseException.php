<?php

declare(strict_types=1);

namespace Amoscato\Integration\Exception;

/**
 * @see https://www.last.fm/api/errorcodes
 */
class LastfmBadResponseException extends \RuntimeException
{
    public const CODE_INVALID_PARAMETERS = 6;

    /** @var object */
    private $responseBody;

    /**
     * @param object $responseBody
     */
    public function __construct($responseBody)
    {
        var_dump($responseBody);
        die;
        parent::__construct($responseBody->message, $responseBody->error);

        $this->responseBody = $responseBody;
    }

    /**
     * @return object
     */
    public function getResponseBody()
    {
        return $this->responseBody;
    }
}
