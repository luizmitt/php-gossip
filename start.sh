#!/bin/bash

port=8010
retry=70

while [ $retry -gt 0 ]
do
    if lsof -Pi :$port -sTCP:LISTEN -t >/dev/null ; then
        let retry-=1
        let port+=1
    else
        break
    fi
done

php -elwsS 0.0.0.0:$port &
port=$port php index.php