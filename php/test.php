<?php

namespace Application;

use phpseclib\Crypt\RSA;
use phpseclib\Net\SSH2;
use Symfony\Component\Yaml\Yaml;


class Test {

    private $config;

    private $output;

    /**
     * Test constructor.
     */
    public function __construct($file)
    {
        $this->config = Yaml::parseFile($file);
    }

    /**
     * Connect to servers
     */
    function connect()
    {

        foreach ($this->config as $host) {

            $output = [
                'available' => false,
                'hostname' => $host['hostname'],
                'name' => $host['name'],
            ];

            $key = new RSA();
            $key->loadKey(file_get_contents('/root/.ssh/id_rsa'));

            // Domain can be an IP too
            $ssh = new SSH2($host['hostname']);
            if ($ssh->login($host['ssh_user'], $key)) {

                // test if we are successfully elevated
                if (
                    $host['remote_user'] &&
                    trim($ssh->exec('sudo -u '.$host['remote_user'].' whoami')) == $host['remote_user']
                ) {
                    $output['available'] = true;
                }

                if ($host['paths']) {

                    $output['changed'] = [];

                    foreach ($host['paths'] as $path) {

                        $statCommand = "stat -c '%A-%U-%G' ".$path['path'];
                        $fileStatInitial = trim($ssh->exec($statCommand));

                        $ssh->exec('sudo mkdir -m '.$path['mode'].' -p '.$path['path']);
                        $ssh->exec('sudo adduser -D '.$path['owner']);
                        $ssh->exec('sudo chown '.$path['owner'].':'.$path['group']);

                        $fileStat = trim($ssh->exec($statCommand));

                        if ($fileStatInitial != $fileStat) {
                            $output['changed'][] = $path['path'];
                        }
                    }
                }

            }

            $this->output[] = $output;

        }

    }

    /**
     * Print the output
     */
    function dump()
    {
        echo preg_replace('/^(  +?)\\1(?=[^ ])/m', '$1', json_encode($this->output, JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT));
    }

}

include 'vendor/autoload.php';

$test = new Test($argv[1]);
$test->connect();
$test->dump();