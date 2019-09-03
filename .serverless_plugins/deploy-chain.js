'use strict';

class DeployChain {
    constructor(serverless, options) {
        this.serverless = serverless;
        this.options = options;

        this.commands = {
            ssh: {
                lifecycleEvents: ['show'],
                options: {
                    verbose: {
                        usage: 'Increase verbosity',
                        shortcut: 'v'
                    }
                }
            },
            protectTermination: {
                lifecycleEvents: ['enable'],
                options: {
                    verbose: {
                        usage: 'Increase verbosity',
                        shortcut: 'v'
                    }
                }
            },
        };

        this.hooks = {
            'ssh:show': () => Promise.resolve().then(this.ssh.bind(this)),
            'protectTermination:enable': () => Promise.resolve().then(this.setTerminationProtection.bind(this)),
            'before:deploy:deploy': () => Promise.resolve().then(this.upsertKeyPair.bind(this)),
            'after:deploy:deploy': () => Promise.resolve().then(this.deployChain.bind(this)),
            'remove:remove': () => Promise.resolve().then(this.remove.bind(this)),
        };
    }

    setTerminationProtection() {
        this.exec("aws cloudformation update-termination-protection --enable-termination-protection --stack-name " + this.getConfig().uuid + this.setRegionArgument() + this.setProfileArgument());
    }

    upsertKeyPair() {
        if (this.hasKey(this.getConfig().uuid)) {
            this.serverless.cli.log('Key (' + this.getConfig().uuid + ') already exists');
            return;
        }

        const pem = JSON.parse(this.exec("aws ec2 create-key-pair --key-name " + this.getConfig().uuid + this.setRegionArgument() + this.setProfileArgument()));
        this.exec("aws ssm put-parameter --name " + this.getConfig().uuid + " --type String --value '" + pem.KeyMaterial + "' --overwrite --region " + this.getConfig().region + this.setProfileArgument());
    }

    ssh() {
        if (!this.hasParameter(this.getConfig().uuid)) {
            this.serverless.cli.log('SSH Key does not exists in Parameter Store');
            return;
        }

        const key = JSON.parse(this.exec("aws ssm get-parameter --name " + this.getConfig().uuid + this.setRegionArgument() + this.setProfileArgument()));
        const keyFile = "~/.ssh/" + this.getConfig().uuid;
        this.exec("echo '" + key.Parameter.Value + "' >> " + keyFile);
        this.exec("chmod 600 " + keyFile);

        const IP = this.getBastionHostIP();
        const MySqlHost = this.getMySqlHost();
        const MySqlPassword = this.getDBPassword();

        this.serverless.cli.log("\n\n\n");
        this.serverless.cli.log("-----------------------------");
        this.serverless.cli.log('-- SSH Credentials');
        this.serverless.cli.log("-----------------------------");
        this.serverless.cli.log("ssh ec2-user@" + IP + " -i " + keyFile);
        this.serverless.cli.log('MySql HOST: ' + MySqlHost);
        this.serverless.cli.log('MySql Username: forge');
        this.serverless.cli.log('MySql Password: ' + MySqlPassword);
        this.serverless.cli.log('MySql Database: forge');
    }

    deployChain() {
        this.exec("serverless invoke -f artisan --data '{\"cli\":\"migrate --force\"}' --stage " + this.getConfig().stage + this.setProfileArgument('--aws-profile') + this.setRegionArgument());
        this.exec("aws s3 sync ./application/public s3://" + this.getConfig().uuid + "-assets --delete --acl public-read " + this.setProfileArgument() + this.setRegionArgument());
    }

    remove() {
        if (this.hasKey(this.getConfig().uuid)) {
            this.exec("aws ec2 delete-key-pair --key-name " + this.getConfig().uuid + this.setRegionArgument() + this.setProfileArgument());
        }

        if (this.hasParameter(this.getConfig().uuid)) {
            this.exec("aws ssm delete-parameter --name " + this.getConfig().uuid + this.setRegionArgument() + this.setProfileArgument());
        }

        this.exec("rm ~/.ssh/" + this.getConfig().uuid);
    }

    /////////

    getDBPassword() {
        const result = JSON.parse(this.exec("aws secretsmanager get-secret-value --secret-id " + this.getConfig().uuid + "-DB_PASSWORD " + this.setRegionArgument() + this.setProfileArgument()));
        return result.SecretString;
    }

    getMySqlHost() {
        let IP = null;

        const instances = JSON.parse(this.exec("aws rds describe-db-clusters --max-items 200 " + this.setRegionArgument() + this.setProfileArgument()));

        instances.DBClusters.forEach(item => {
            if (item.Status === 'available' && item.Endpoint.substring(0, this.getConfig().uuid.length) === this.getConfig().uuid) {
                IP = item.Endpoint;
            }
        });

        return IP;
    }

    getBastionHostIP() {
        let IP = null;

        const instances = JSON.parse(this.exec("aws ec2 describe-instances --filters 'Name=tag:Name,Values=" + this.getConfig().uuid + "'" + this.setRegionArgument() + this.setProfileArgument()));

        instances.Reservations.forEach(item => {
            item.Instances.forEach(instance => {
                if (instance.State.Name === 'running') {
                    IP = instance.PublicIpAddress;
                }
            });
        });

        return IP;
    }

    getConfig() {
        return {
            uuid: this.serverless.service.custom.UUID,
            stage: this.serverless.service.custom.STAGE,
            region: this.serverless.service.custom.REGION,
            profile: process.env.AWS_PROFILE || this.options['aws-profile']
        };
    }

    setProfileArgument(argumentName = '--profile') {
        if (this.getConfig().profile) {
            return ' ' + argumentName + ' ' + this.getConfig().profile;
        }

        return '';
    }

    setRegionArgument() {
        if (this.getConfig().region) {
            return ' --region ' + this.getConfig().region;
        }

        return '';
    }

    hasKey(name) {
        const items = JSON.parse(this.exec("aws ec2 describe-key-pairs --filters 'Name=key-name,Values=" + name + "'" + this.setRegionArgument() + this.setProfileArgument()));

        if (items.KeyPairs.length && items.KeyPairs.filter(key => {
            return key.KeyName === name
        }).length) {
            return true;
        }
    }

    hasParameter(name) {
        const items = JSON.parse(this.exec("aws ssm describe-parameters --filters 'Key=Name,Values=" + name + "'" + this.setRegionArgument() + this.setProfileArgument()));

        if (items.Parameters.length && items.Parameters.filter(key => {
            return key.Name === name
        }).length) {
            return true;
        }
    }

    exec(command) {
        const execSync = require('child_process').execSync;
        this.serverless.cli.log(command);
        const res = execSync(command).toString();

        if (this.options.verbose || process.env.SLS_DEBUG) {
            this.serverless.cli.log(res);
        }

        return res;
    }
}

module.exports = DeployChain;