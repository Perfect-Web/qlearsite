#!/bin/bash

function prepareContainers {
   docker-compose down && docker-compose up -d && sleep 5
}

echo '#################################################'
echo 'Starting Qlearsite automatation'
echo '#################################################'

echo -e '\n> preparing containers'
prepareContainers

echo -e '\n> Running PHP implementation of Qlearsite'
echo -e '>> preparing code'
docker-compose exec php ./composer.phar install
echo -e '\n>> running tests'
docker-compose exec php php test.php /instances.yaml
echo -e '\n>> running 2nd test'
docker-compose exec php php test.php /instances.yaml

echo -e '\n> Running node.js implementation of Qlearsite'
echo -e '>> preparing code'
prepareContainers &>/dev/null
docker-compose exec node npm install
echo -e '\n>> running tests'
docker-compose exec node node test.js
echo -e '\n>> running 2nd test'
docker-compose exec node node test.js

echo -e '\n> Running python implementation of Qlearsite'
echo -e '>> preparing code'
prepareContainers &>/dev/null
docker-compose exec python pip install pyyaml cryptography==2.4.2 paramiko
echo -e '\n>> running tests'
docker-compose exec python python test.py /instances.yaml
echo -e '\n>> running 2nd test'
docker-compose exec python python test.py /instances.yaml

echo -e '\n> cleaning up'
docker-compose down