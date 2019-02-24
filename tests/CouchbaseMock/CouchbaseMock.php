<?php

declare(strict_types=1);

namespace BowlOfSoup\CouchbaseAccessLayer\Test\CouchbaseMock;

class CouchbaseMock
{
    const VERSION = '1.5.12';

    /** @var string */
    private $jarPath;

    /** @var resource */
    private $ctlServer;

    /** @var resource */
    private $ctl;

    public function __construct()
    {
        $this->jarPath = join(DIRECTORY_SEPARATOR, [__DIR__, "CouchbaseMock.jar"]);
        $this->download();
    }

    /**
     * @param string $version
     */
    public function download(string $version = CouchbaseMock::VERSION)
    {
        if (!file_exists($this->jarPath)) {
            $data = file_get_contents("http://packages.couchbase.com/clients/c/mock/CouchbaseMock-$version.jar");
            file_put_contents($this->jarPath, $data);
        }
    }

    public function start()
    {
        $this->ctlServer = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        socket_bind($this->ctlServer, '127.0.0.1');
        socket_listen($this->ctlServer);
        socket_getsockname($this->ctlServer, $addr, $port);
        $pid = pcntl_fork();

        if ($pid) {
            $this->ctl = socket_accept($this->ctlServer);
        } else {
            $rc = pcntl_exec("/usr/bin/java", [
                "-jar", $this->jarPath,
                "--harakiri-monitor", "{$addr}:{$port}",
                "--port", "0",
                "--replicas", "2",
                "--nodes", "4",
                "--buckets", "default::"
            ]);
            if (!$rc) {
                exit(0);
            }
        }
    }

    public function stop()
    {
        socket_close($this->ctl);
        socket_close($this->ctlServer);
    }

    /**
     * @param $payload
     *
     * @throws \Exception
     *
     * @return array
     */
    protected function send($payload)
    {
        socket_write($this->ctl, json_encode($payload) . "\n");
        $response = socket_read($this->ctl, 100000, PHP_NORMAL_READ);
        $json = strstr($response, '{');
        if (!$json) {
            return [];
        }

        $decoded = json_decode($json, true);
        if (array_key_exists('error', $decoded)) {
            throw new \Exception($decoded['error']);
        }

        return $decoded;
    }

    /**
     * @param bool $enabled
     *
     * @throws \Exception
     */
    public function setCccp(bool $enabled = true)
    {
        $this->send([
            'command' => 'SET_CCCP',
            'payload' => [
                'enabled' => boolval($enabled)
            ]
        ]);
    }

    /**
     * @param array $options
     *
     * @throws \Exception
     *
     * @return string
     */
    public function connectionString(array $options = [])
    {
        $response = $this->send(['command' => 'GET_MCPORTS']);
        $connectionString = "";
        foreach ($response["payload"] as $port) {
            if ($connectionString == "") {
                $connectionString = "couchbase://127.0.0.1:{$port}";
            } else {
                $connectionString .= ",127.0.0.1:{$port}";
            }
        }
        if (count($options)) {
            $connectionString .= '?' . http_build_query($options);
        }

        return $connectionString;
    }
}