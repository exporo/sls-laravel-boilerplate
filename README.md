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
[Credits](#credits)  


## Summary
<a name="summary"/>

We are currently developing a boilerplate for hosting a typical Laravel application serverless  in the AWS Cloud. Therefore we have combined the serverless.com framework, the bref AWS Lambda layers and some AWS Cloudformation scripts. All AWS resources were written as Infrastructure as a Code and being used natively without touching any passwords and secret by hand. If you want to know more why we're not betting on Taylor Otwell's Vapor, [read on here](https://tech.exporo.de/blog/coming-soon).

All resources are defined as a cloudformation template in the serverless.yml file: 
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

All resources were paid in a pay as you go model.

Since all resources were private and hosted in a VPC, an EC2 instance is placed as a bastion host. The instance type is t2.nano and costs about 6 € per month. 

## Requirements
<a name="requirements"/>

* Node.js 6.x or later
* PHP 7.2 or later
* AWS CLI
* Serverless Framework
* An AWS Account 

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
exporo_sls:~$ docker-compose exec php bash
exporo_sls:~$ open http://localhost
bash-4.2# cd /var/task/application/
bash-4.2# php artisan XYZ
```

## Demo application
<a name="demo"/>

This demo application implements various page counters that use different AWS techniques (DB, Cache, Filesystem) to store hits.
The home controller only reads the hits from the resources and triggers an event that stores the hits asynchronously using an SQS queue.
A cron job resets all page counters hourly.


## Migrate your application
<a name="migration"/>

Empty the application folder and insert your Laravel application.
Almost all configurations are done in serverless.yml, but you will need to make some minor changes to your Laravel application:

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

## Todo
<a name="todo"/>

- add queue error / retry  handling
- add db password rotation rotation 

## Credits
<a name="credits"/>
  
* A big thank you goes to Tylor Otwell, who with his framework Laravel and services such as [Vapor](https://vapor.laravel.com/) manages to let us let our technological creativity run free.    
* The creators of the php lambda layers called [bref](https://bref.sh/docs/)
* This post [Migration Guide: Serverless + Bref + Laravel](https://medium.com/no-deploys-on-friday/migration-guide-serverless-bref-laravel-fbb513b4c54b) by Thiago Marini
