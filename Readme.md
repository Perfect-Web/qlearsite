# requirements
* docker
* docker-compose

# how to test
run `./run.sh`

# what does it contain
running the entrypoint of the test suite thru `./run.sh` will:
* create and build containers
* start vhost containers that contain ssh server
* start test containers for each of the php, node.js and python implementation; each test will run 2 times on the same dataset

