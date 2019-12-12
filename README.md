# Gossip
Uma simples rede Gossip com PHP, ainda em testes...
## exemplos

tornar executavel
```sh
chmod +x ./start.sh
```
Colocar os arquivos que queira compartilhar na rede dentro da pasta /shared antes de iniciar o serviço

Peer 1 (IP: 172.17.10.50, PORTA: 8010)
```sh
./start.sh
```

Peer 2 (IP: 172.17.10.84, PORTA: 8011)
```sh
peer=172.17.10.50:8010 ./start.sh
```

Peer 3 (IP: 172.17.10.34, PORTA: 8010)
```sh
peer=172.17.10.84:8011 ./start.sh
```

Peer n (IP: xxx.xxx.xxx.xxx, PORTA: xxxx)
```sh
peer=(Ip e Porta de um Peer ativo) ./start.sh
```

OBS: Quando não é informado o peer você mantém uma rede propria. Para conectar na sua rede é necessário informar para quem for conectar o ip e a porta.