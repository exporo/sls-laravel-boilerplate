[![CircleCI](https://circleci.com/gh/Exporo-AG/exporo_sls_laravel.svg?style=svg)](https://circleci.com/gh/Exporo-AG/exporo_sls_laravel)

# Exporo Serverless Laravel Boilerplate   

##### Table of Contents  
[Summary](#summary)  
[Requirements](#requirements)  
[Installation](#installation)  
[Deployment](#deployment)  
[Assets](#assets)  
[Local development](#local)  
[Demo application](#demo)  
[Migrate your application](#migration)  
[CI/CD](#CICD)  
[TODOs](#todo)   
[Credits](#credits)  


## Summary
<a name="summary"/>

We are currently developing a boilerplate for hosting a typical Laravel application serverless in the AWS Cloud. 
We have combined the serverless.com framework, bref AWS Lambda and some AWS Cloudformation scripts to accomplish this. 
All AWS resources have been written as Infrastructure as Code. They are being used natively without the need to access passwords or secrets by hand. 
BTW: If you'd like to know why we're not betting on Tyler Otwell's Vapor to accomplish what we can, read on [here](https://tech.exporo.com/blog/sls-laravel-agility/).. 

All resources are defined as a Cloudformation template in the serverless.yml file:
```yml
 environment:
    APP_KEY: !Sub '{{resolve:secretsmanager:${self:custom.UUID}-APP__KEY}}'
    APP_STORAGE: '/tmp'
    DB_HOST:
      Fn::GetAtt: [AuroraRDSCluster, Endpoint.Address]
    DB_PASSWORD: !Sub '{{resolve:secretsmanager:exporo-sls-laravel-dev-DB__PASSWORD}}'
    LOG_CHANNEL: stderr
    SQS_REGION: ${self:provider.region}
    VIEW_COMPILED_PATH: /tmp/storage/framework/views
    CACHE_DRIVER: dynamodb
    SESSION_DRIVER: dynamodb
    QUEUE_CONNECTION: sqs
    SQS_QUEUE: !Ref SQSQueue
    DYNAMODB_CACHE_TABLE: !Ref DynamoDB
    FILESYSTEM_DRIVER: s3
    AWS_BUCKET: !Ref S3Bucket
```

* AWS DynamoDB as  a Session driver
* AWS DynamoDB as a Cache driver
* AWS RDS Aurora serverless MySQL 5.6 as a Database
* AWS S3 as a Storage provider
* AWS Lambda event for triggering the cron jobs
* AWS SQS + Lambda Event for queueing processes

All resources have been paid for in a pay-as-you-go model. 

Since all resources are located in private subnets and hosted in a VPC, an EC2 instance is placed in a public subnet as a bastion host and NAT instance.
The NAT instance replaces a NAT gateway (~ 40€/month) with which Lambda functions can access the Internet. 
The instance type is t2.nano and costs about 5€ per month. 

Some load tests around RDS Aurora Serverless ACUs sizes can be found [here](https://tech.exporo.com/blog/serverless-laravel-rds-serverless-benchmark/).

In some places this project is still a bit raw, because it is still quite new, so **feel free to contribute!**
 

## Requirements
<a name="requirements"/>

* Node.js 6.x or later
* PHP 7.2 or later
* AWS CLI
* Serverless Framework
* An AWS Account 
* A domain hosted in your AWS Account (If you don't have one read the section **Domain registration**) 

## Installation
<a name="installation"/>

```console
exporo_sls:~$ aws configure   
exporo_sls:~$ npm install -g serverless   
exporo_sls:~$ npm install  
exporo_sls:~$ composer install   
exporo_sls:~$ application/composer install  
```

## Deployment
<a name="deployment"/>

**Deployment**
```console
exporo_sls:~$ php artisan config:clear
exporo_sls:~$ serverless deploy --stage {stage} --aws-profile default
```

**Deployment chain**  
A serverless plugin *./serverless_plugins/deploy-chain.js* automatically creates an EC2 key pair and stores it in the AWS Parameter Store.
This plugin is still quite raw and the aws cli commands could generally be exchanged for the AWS SDK.
After deployment, the following steps were performed:
```console
exporo_sls:~$ serverless invoke -f artisan --data '{"cli":"migrate --force"}' --stage {stage} --aws-profile {profile}
exporo_sls:~$ aws s3 sync ./application/public s3://${service-name}-${stage}-assets --delete --acl public-read --profile {profile}
```

**Local database access**  
The same plugin fetches and displays all necessary parameters to access the database: 
```console
exporo_sls:~$ serverless ssh --stage {stage} --aws-profile default
```
Output:
```console
Serverless: -----------------------------
Serverless: -- SSH Credentials
Serverless: -----------------------------
Serverless: ssh ec2-user@18.185.33.123 -i ~/.ssh/exporo-sls-laravel-nat-instance
Serverless: MySql HOST: exporo-sls-laravel-nat-instance-aurorardscluster-scl4vnp4lyet.cluster-cadypvf3voom.eu-central-1.rds.amazonaws.com
Serverless: MySql Username: forge
Serverless: MySql Password: &a%<40I)ln]oo>F7Q]jUG!3OsVb2vM
Serverless: MySql Database: forge
```



## Assets
<a name="assets"/>

In addition to the private S3 bucket for the Laravel storage, a public bucket is created for the assets.

Assets should be used in the views like this:
```php
<img width="400px" src="{{ asset('exporo-tech.png') }}">
```

In the deployment chain, the S3 bucket should be synchronized with the public folder.
```shell
aws s3 sync public s3://${service-name}-${stage}-assets --delete --acl public-read --profile default
```

**local environment**  
Another docker container, for the delivery of the assets, with the address localhost:8080 will be built for the local environment. 

## Local development
<a name="local"/>

```console
exporo_sls:~$ docker-compose up -d
exporo_sls:~$ open http://localhost
exporo_sls:~$ docker-compose exec php bash
bash-4.2# cd /var/task/application/
bash-4.2# php artisan XYZ
```

## Demo application
<a name="demo"/>

The demo application implements various page counters using different AWS techniques including DB, Cache and Filesystem to store hits. 
The home controller only reads hits from resources and triggers an event that stores the hits asynchronously using as SQS queue. 
A cron job resets all page counters hourly. 

## Migrate your application
<a name="migration"/>

Empty the application folder, and insert your Laravel application. 
Almost all configurations are done in serverless.yml, but you will need to make a few minor changes to your Laravel application. 

##### 1: Add composer dependencies to your project

```console
exporo_sls:~$ application/composer require league/flysystem-aws-s3-v3
exporo_sls:~$ application/composer require bref/bref "^0.5"
```

##### 2: Removing error-causing env variables
Replace key and secret env vars with '' in:
- dynamodb in config/cache.php
- sqs in config/queue.ph
- s3 in config/filesystems.php


For example dynamodb in config/cache.php:
```php
'dynamodb' => [
            'driver' => 'dynamodb',
            'key' => '',
            'secret' => '',
            'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
            'table' => env('DYNAMODB_CACHE_TABLE', 'cache'),
            'endpoint' => env('DYNAMODB_ENDPOINT'),
        ],
```

##### 3: Update local filesystem
Update the root folder in **config/filesystem.php** to:

```php
'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => env('APP_STORAGE', storage_path('app')),
        ],
```

##### 4: Set storage directory and create a temporary directory
Add this to the boot method in **app/Providers/AppServiceProvider.php**:

```php
app()->useStoragePath(env('APP_STORAGE', $this->app->storagePath()));

if (! is_dir(config('view.compiled'))) {
    mkdir(config('view.compiled'), 0755, true);
}
```

##### 5: Example application/.env
```
APP_KEY=base64:c3SzeMQZZHPT+eLQH6BnpDhw/uKH2N5zgM2x2a8qpcA=
APP_ENV=dev
APP_DEBUG=true

LOG_CHANNEL=stderr
APP_STORAGE=/tmp
CACHE_DRIVER=redis
SESSION_DRIVER=redis
REDIS_HOST=redis
VIEW_COMPILED_PATH=/tmp/storage/framework/views

DB_HOST=mysql
DB_USERNAME=homestead
DB_PASSWORD=secret
DB_DATABASE=forge

ASSET_URL=http://localhost:8080
```

## CI/CD
<a name="CICD"/>

### CircleCI Jobs
We also added an exemplary CircleCi pipeline in this boilerplate.
This consists of the following jobs:

**build => test => deploy => smoke test**

If the branch is the master, two more jobs will be added:

***build => test => deploy => smoke test => approval button => deploy to production**

The deploy job deploys the respective one into your AWS account and creates a corresponding subdomain at the same time:
**{BranchName}.{BaseDomain}**

The branch name consists of the first 11 characters of the branch. Unfortunately we have to do this because we have run into an AWS limit because our service name "exporo-sls-laravel" and the full branch name have exceeded a character limit.

*.circleci/config.yml*
```
- run:
    name: Define stage name
    command: |
      echo "export STAGE=$(echo $CIRCLE_BRANCH | cut -c1-11 | sed -e 's/\//-/g')" >> $BASH_ENV
      echo "export STAGE=$STAGE" >> envVars
      echo "STAGE: $STAGE"
```

The Basedomain is configured in the *serverless.yml* file.
As you can see, a production deployment does not use a subdomain but directly the basedomain:
```
customDomain:
    domainName: ${self:custom.customDomain.switch.${opt:stage, self:provider.stage}.domainName , '${self:custom.customDomain.swicth.default.domainName}'}
    swicth:
      baseDomain: techdev2.exporo.de
      default:
        domainName: ${opt:stage, self:provider.stage}.${self:custom.customDomain.switch.baseDomain}
      production:
        domainName: ${self:custom.customDomain.switch.baseDomain}
```

Another difference with a production deployment are some RDS Aurora settings in the *serverless.yml*:

```
AURORA:
    MIN_CAPACITY: 1
    MAX_CAPACITY: 8
    AUTO_PAUSE_SECONDS: 600
    DELETE_PROTECTION: ${self:custom.AURORA.SWITCH.${opt:stage, self:provider.stage}.DELETE_PROTECTION , '${self:custom.AURORA.SWITCH.default.DELETE_PROTECTION}'}
    AUTO_PAUSE: ${self:custom.AURORA.SWITCH.${opt:stage, self:provider.stage}.AUTO_PAUSE , '${self:custom.AURORA.SWITCH.default.AUTO_PAUSE}'}
    SWITCH:
      production:
        AUTO_PAUSE: false
        DELETE_PROTECTION: true
      default:
        AUTO_PAUSE: 'true'
        DELETE_PROTECTION: 'false'
```

We have configured the database so that it does not pause in a production environment. Furthermore, it can not be simply deleted by cloudformation.
The same is done for the complete cloudformation stack in the CircleCi deploy prod job with the protectTermination command.
The protectTermination command is also implemented in our deploy chain plugin.

```
- run:
    name: Set delete protection
    command: |
      serverless protectTermination --stage production
```


### Domain registration 
For registering subdomains for the different stacks we use the [serverless-domain-manager](https://www.npmjs.com/package/serverless-domain-manager) plugin.
If you don't have a domain in your AWS account you can simply delete this plugin from the servless.yml file. If you use the CircleCI template remove this command "serverless create_domain" from the deploy Jobs.

### Smoke Test 
To test the deployed infrastructure, e.g. if the Lambda function has access to the internet or the Storage S3 Bucket does not allow public access, we wrote a SmokeTestController. This is checked in a CircleCi job.
*application/app/Http/Controllers/SmokeTestController.php*



## TODOs
<a name="todo"/>

- add queue error / retry  handling
- display stderr from scheduled commands 
- remove unused/merged stacks


## Credits
<a name="credits"/>
  
* A big thanks is due to Tyler Otwell, whose framework Laravel and [Vapor](https://vapor.laravel.com/) service allows us to let our technological creativity run free.    
* The creators of the php Lambda layers called  [bref](https://bref.sh/docs/)
* The post [Migration Guide: Serverless + Bref + Laravel](https://medium.com/no-deploys-on-friday/migration-guide-serverless-bref-laravel-fbb513b4c54b) by Thiago Marini
