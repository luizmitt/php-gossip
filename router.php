<?php

require_once __DIR__ . '/Gossip.php';

$gossip = new Gossip();

// broadcast
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'POST' && $_SERVER['PATH_INFO'] == '/broadcast') {
    $data = json_decode(file_get_contents("php://input"), true);

    if ($data) {
        $gossip->save($data);
    }
}
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'POST' && $_SERVER['PATH_INFO'] == '/sync') {
    $data = json_decode(file_get_contents("php://input"), true);

    if ($data) {
        @file_put_contents(__DIR__ . "/shared/{$data['filename']}", base64_decode($data['binary']));
        @file_put_contents(__DIR__ . "/shared/{$data['fileinfo']}", json_encode($data));
    }
}