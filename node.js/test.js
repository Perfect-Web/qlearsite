var yaml = require('js-yaml');
var fs = require('fs');
var SSH2Promise = require('ssh2-promise');

var jsonOutput = []
var hosts = yaml.safeLoad(fs.readFileSync('../instances.yaml', 'utf8'));

(async () => {

  for(var i = 0; i < hosts.length; i++) {

    await (async () => {

      const host = hosts[i];

      var ssh = new SSH2Promise({
        host: host['hostname'],
        username: host['ssh_user'],
        identity: '/root/.ssh/id_rsa'
      });

      var output = {
        'available': false,
        'hostname': host['hostname'],
        'name': host['name']
      };

      await ssh.connect();

      const whoami = await ssh.exec('sudo -u '+host['remote_user']+' whoami').catch(e => {});
      if(whoami && whoami.trim() === host['remote_user']) {
        output['available'] = true;
      }

      if ('paths' in host && host['paths'].length) {

        if (!('changed' in output)) output['changed'] = [];

        for (const index in host['paths']) {

          path = host['paths'][index]
          var statCommand = "stat -c '%A-%U-%G' " + path['path'];
          await ssh.exec('sudo mkdir -m '+path['mode']+' -p '+path['path']).catch(e => {});
          let fileStatInitial = (await ssh.exec(statCommand)).trim();

          // user may exist already
          await ssh.exec('sudo adduser -D '+path['owner']).catch(e => {});
          await ssh.exec('sudo chown '+path['owner']+':'+path['group']+' '+path['path']).catch(e => {})

          if (fileStatInitial != (await ssh.exec(statCommand)).trim()) {
            output['changed'].push(path['path']);
          }

        }

      }

      jsonOutput.push(output)

    })()

  }

  console.log(jsonOutput)
  process.exit()

})().catch(e => console.log(e.toString()))