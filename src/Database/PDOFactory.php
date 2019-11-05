<?php

declare(strict_types=1);

namespace Amoscato\Database;

use PDO;

class PDOFactory
{
    /** @var string */
    private $dsn;

    /** @var string */
    private $username;

    /** @var string */
    private $password;

    /** @var array */
    private $options;

    /**
     * @param string $dsn
     * @param string $username optional
     * @param string $password optional
     * @param array $options optional
     */
    public function __construct($dsn, $username = null, $password = null, array $options = [])
    {
        $this->dsn = $dsn;
        $this->username = $username;
        $this->password = $password;
        $this->options = $options;
    }

    public function getInstance(): PDO
    {
        return new PDO(
            $this->dsn,
            $this->username,
            $this->password,
            $this->options
        );
    }
}
