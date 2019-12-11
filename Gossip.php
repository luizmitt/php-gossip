<?php
/**
 * Luiz Schmitt <lzschmitt@gmail.com>
 * 
 * Uma Simples Rede Gossip...
 * 
 */

class Gossip
{
    public function __construct()
    {
        $this->connect([
            'server' => 'http://' . gethostbyname(gethostname()) . ':' . getenv('port')
        ]);
    }

    public function getPeers()
    {
        return (file_exists(__DIR__ . '/peers.list')) ? json_decode(@file_get_contents(__DIR__ . '/peers.list'), true) : [];
    }

    public function connect($data, $list=false)
    {
        if (!$list) {
            if ($peers = $this->getPeers()) {
                foreach ($peers as $index=>$peer) {
                    if ($peer['server'] === $data['server']) {
                        return;
                    }
                }
            }

            $peers[] = $data;
        } else {
            $peers = $data;
        }

        @file_put_contents(__DIR__ . '/peers.list', json_encode($peers));
    }

    public function disconnect($peer)
    {
        echo "{$peer['server']} desconectou do nó!\r\n";

        if ($peers = $this->getPeers()) {
            foreach ($peers as $index=>$p) {
                if ($p['server'] == $peer['server']) {
                    unset($peers[$index]);
                    $this->connect($peers, true);
                }
            }
        }
    }

    public function broadcast()
    {
        echo time() . " broadcast network\r\n";

        if ($peers = $this->getPeers()) {
            foreach ($peers as $index=>$peer) {
                echo "{$peer['server']} updating...\r";
                $broadcast = @file_get_contents("{$peer['server']}/broadcast.php", null, stream_context_create([
                    'http' => [
                        'method'  => 'POST',
                        'header'  => "Content-Type: application/json\r\n",
                        'content' => json_encode($peers)
                    ]
                ]));

                if (!$broadcast) {
                    $this->disconnect($peer);
                }
            }
        }
    }

    public function requestUpdate()
    {
        if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'POST') {
            $data = json_decode(file_get_contents("php://input"), true);

            if ($data) {
                $this->connect($data, true);
            }

            echo 1;
        }
    }

    public function loop()
    {
        while(true) {
            $this->broadcast();
            usleep(2000000);
        }
    }
}