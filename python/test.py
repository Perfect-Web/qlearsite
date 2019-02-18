import yaml, json, sys, paramiko, time

class Test:

  output = []

  def __init__(self, filename):
    with open(filename, 'r') as stream:
      self.config = yaml.load(stream)

  def connect(self):

    for host in self.config:

      output = {
        "available": False,
        "hostname": host['hostname'],
        "name": host['name']
      }

      key = paramiko.RSAKey.from_private_key_file('/root/.ssh/id_rsa')
      client = paramiko.SSHClient()
      client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
      client.connect(host['hostname'], username=host['ssh_user'], pkey = key)

      if 'remote_user' in host:
        stdin, stdout, stderr = client.exec_command('sudo -u '+host['remote_user']+' whoami')
        if stdout.readlines()[0].strip() == host['remote_user']:
          output["available"] = True

      if 'paths' in host:

        if 'changed' not in output:
          output['changed'] = []

        for path in host['paths']:

          statCommand = "stat -c '%A-%U-%G' "+path['path'];
          stdin, stdout, stderr = client.exec_command(statCommand)
          fileStatInitial = stdout.read().strip().decode("utf-8")

          client.exec_command('sudo mkdir -m '+path['mode']+' -p '+path['path'])
          client.exec_command('sudo adduser -D '+path['owner'])
          client.exec_command('sudo chown '+path['owner']+':'+path['group'])

          stdin, stdout, stderr = client.exec_command(statCommand)
          fileStat = stdout.read().strip().decode("utf-8")

          if fileStatInitial != fileStat:
            output['changed'].append(path['path'])

      self.output.append(output)

  def dump(self):
    sys.stdout.write(json.dumps(self.output, sort_keys=True, indent=2))

test = Test(sys.argv[1]);
test.connect()
test.dump()