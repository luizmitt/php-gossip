<?php

class Gossip {

    const PEER_PATH  = __DIR__ . '/peers.list';
    const SHARE_PATH = __DIR__ . '/shared';

    public function __construct()
    {
        $protocol = (getenv('protocol') != null) ? getenv('protocol') : 'http';
        $ip       = (getenv('ip') != null) ? getenv('ip') : gethostbyname(gethostname());
        $port     = (getenv('port') != null) ? getenv('port') : null;
        $peer     = (getenv('peer') != null) ? getenv('peer') : null;

        if (!$port) {
            $this->notify("Não é possível criar uma peer sem uma porta de conexão!", "err");
            return;
        }

        // adiciona um peer se existir
        if ($peer) {
            $parts = explode(':', $peer);
            $peer_ip   = $parts[0];
            $peer_port = $parts[1];

            $this->connect([
                'protocol' => 'http',
                'ip'       => $peer_ip,
                'port'     => $peer_port
            ]);
        }

        // se adiciona na rede
        $this->connect([
            'protocol' => $protocol,
            'ip'       => $ip,
            'port'     => $port
        ]);
    }

    // tras a url do peer
    public function getUrl($peer)
    {
        return "{$peer['protocol']}://{$peer['ip']}:{$peer['port']}";
    }

    // colore as mensagens de acordo com um tipo
    public function notify($message, $type = 'warn')
    {
        switch ($type) {
            case 'warn':
                $code = 33;
            break;

            case 'err':
                $code = 31;
            break;

            case 'suss':
                $code = 32;
            break;

            case 'info':
                $code = 36;
            break;

            default:
                $code = 33;
        }

        echo "\033[{$code};40m{$message}\033[0m\n";
    }

    // retorna uma lista dos peers ativos
    public function getPeers()
    {
        $list = [];

        if (file_exists(self::PEER_PATH)) {
            $peers = json_decode(@file_get_contents(self::PEER_PATH), true);

            if (!empty($peers)) {
                foreach ($peers as $peer) {
                    if (@file_get_contents($this->getUrl($peer) . '/peers.list', null, stream_context_create(['http' => ['timeout' => 5, 'method' => 'GET']]))) {
                        $list[] = $peer;
                        $this->notify("* {$this->getUrl($peer)} online.", "suss");
                    } else {
                        $this->notify("* {$this->getUrl($peer)} offline.", "err");
                    }
                }
            }
        }

        return $list;
    }

    // atualiza a lista de peers
    public function save(array $list)
    {
        if(@file_put_contents(self::PEER_PATH, json_encode($list))) {
            $this->notify("* lista atualizada", "suss");
        } else {
            $this->notify("* lista não foi atualizada", "err");
        }

        return;
    }

    // conecta um peer ou uma lista no nó
    public function connect($data)
    {
        $found = 0;
        $peers = $this->getPeers();

        foreach ($peers as $peer) {
            if ($this->getUrl($peer) === $this->getUrl($data)) {
                $found++;
            }
        }

        if ($peers && !$found) {
            $this->notify("* O peer " . $this->getUrl($data) . " conectou.", "suss");
            $peers[] = $data;

            $this->save($peers);
        } else {
            // genesis
            $this->save([$data]);
        }
    }

    // disconecta um peer do nó
    public function disconnect($data)
    {
        $peers = $this->getPeers();

        foreach ($peers as $index => $peer) {
            if ($this->getUrl($peer) === $this->getUrl($data)) {
                $this->notify("* O peer " . $this->getUrl($data) . " desconectou.", "err");
                unset($peers[$index]);
                $this->save($peers);
            }
        }
    }

    // notifica todos os peers do nó
    public function broadcast()
    {
        $peers = $this->getPeers();

        foreach ($peers as $peer) {
            $this->notify("* broadcast " . $this->getUrl($peer) . "...");
            @file_get_contents($this->getUrl($peer) . '/router.php/broadcast', null, stream_context_create([
                'http' => [
                    'method' => 'POST',
                    'header' => "Content-Type: application/json",
                    'content' => json_encode($peers)
                ]
            ]));
        }
    }

    public function loop()
    {
        while(true) {
            usleep(5000000);
            $this->broadcast();
            usleep(3000000);
            $this->sync();
        }
    }

    public function sync() {

        foreach (glob(self::SHARE_PATH . '/*') as $file) {
            if (file_exists($file)) {
                $binary     = base64_encode(@file_get_contents($file));
                $parts      = explode("/", $file);
                $filename   = end($parts);

                if(preg_match("/(.+)(\.[a-zA-Z]{2,3})/", $filename, $matched)) {
                    $fileinfo = $matched[1] ? $matched[1] : $filename;
                    $fileinfo = ".{$fileinfo}.fileinfo";
                }

                $data = [
                    'filename' => $filename,
                    'binary'   => $binary,
                    'size'     => strlen($binary),
                    'md5sum'   => md5_file($file),
                    'fileinfo' => $fileinfo,
                    'datetime' => time()
                ];

                if ($peers = $this->getPeers()) {
                    foreach ($peers as $peer) {
                        if (@file_get_contents($this->getUrl($peer) . '/router.php/sync', null, stream_context_create(['http' => ['method' => 'POST' , 'header' => "Content-Type: application/json\r\n", 'content' => json_encode($data), 'timeout' => 30]]))) {
                            $this->notify("* {$this->getUrl($peer)} sync OK!");
                        }
                    }
                }
            }
        }
    }
}