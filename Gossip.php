<?php
/**
 * Luiz Schmitt <lzschmitt@gmail.com>
 * 
 * Uma Simples Rede Gossip...
 * 
 */

class Gossip
{
    public function __construct($file = null)
    {
        $ip     = (getenv('ip') != null) ? getenv('ip') : gethostbyname(gethostname());
        $port   = getenv('port');
        $peer   = getenv('peer') ?? null;

        $this->connect([
            'server' => "http://{$ip}:{$port}"
        ]);

        if ($peer) {
            $this->connect([
                'server' => "http://{$peer}"
            ]);
        }
    }

    public function upload() {
        $path = __DIR__ . "/shared/";

        foreach (glob($path . '/*') as $file) {
            if (file_exists($file)) {
                $binary     = base64_encode(file_get_contents($file));
                $parts      = explode("/", $file);
                $filename   = end($parts);

                if ($peers = $this->getPeers()) {
                    foreach ($peers as $index=>$peer) {
                        if ($this->request("{$peer['server']}/upload.php", ['name' => $filename, 'file' => $binary], 'POST')) {
                            echo "* {$peer['server']} file uploaded!\r\n";
                        }
                    }
                }
            }
        }
    }

    public function requestUpload()
    {
        if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'POST') {
            $data = json_decode(file_get_contents("php://input"), true);
            file_put_contents(__DIR__ . "/shared/{$data['name']}", base64_decode($data['file']));
        }

        echo 1;
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

            echo "* {$data['server']} connected!\r\n";
            $peers[] = $data;
        } else {
            echo "* peers updated!\r\n";
            $peers = $data;
        }

        @file_put_contents(__DIR__ . '/peers.list', json_encode($peers));
    }

    public function disconnect($peer)
    {
        echo "* {$peer['server']} disconnected!\r\n";

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
        echo "* checking connections....\r\n";
        $this->makePersistente();

        echo "* broadcast network\r\n";
        if ($peers = $this->getPeers()) {
            foreach ($peers as $index=>$peer) {
                $broadcast = $this->request("{$peer['server']}/broadcast.php", $peers);

                if (!$broadcast) {
                    $this->disconnect($peer);
                }
            }
        }

        echo "* checking files...\r\n";
        $this->upload("arquivo.txt");
    }

    public function request($url, $file, $method = 'POST')
    {
        return @file_get_contents($url, null, stream_context_create([
            'http' => [
                'timeout' => '10',
                'method'  => strtoupper($method),
                'header'  => "Content-Type: application/json\r\n",
                'content' => json_encode($file)
            ]
        ]));
    }

    public function makePersistente()
    {
        if ($peers = $this->getPeers()) {
            foreach ($peers as $index=>$peer) {
                if (!$this->request($peer['server'] . '/peers.list', [], 'GET')) {
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
        }

        echo 1;
    }

    public function loop()
    {
        while(true) {
            $this->broadcast();
            usleep(4000000);
        }
    }
}