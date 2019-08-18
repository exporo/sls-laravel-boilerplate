# Exporo Serverless Laravel Boilerplate   

##### Table of Contents  
[Summary](#summary)  
[Requirements](#requirements)  
[Installation](#installation)  
[Deployment](#deployment)  
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

* Create a  keypair with the name *exporo-sls-laravel* in your AWS account. (AWS Web Console > EC2 > KEYPAIR)


```console
exporo_sls:~$ aws configure   
exporo_sls:~$ npm install -g serverless   
exporo_sls:~$ npm install  
exporo_sls:~$ composer install   
exporo_sls:~$ application/composer install  
```

## Deployment
<a name="deployment"/>

```console
exporo_sls:~$ php artisan config:clear
exporo_sls:~$ serverless deploy --stage {stage} --aws-profile default
exporo_sls:~$ $serverless invoke -f artisan --data '{"cli":"migrate --force"}' --stage {stage} --aws-profile default
```

## Local development
<a name="local"/>

```console
exporo_sls:~$ docker-compose up -d
exporo_sls:~$ docker-compose exec webapp bash
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

##### 2: Make storage path configurable
Add this line to **bootstrap/app.php**


```php
$app->useStoragePath($_ENV['APP_STORAGE'] ?? $app->storagePath());
```


##### 3: Removing error-causing env variables
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


##### 4: Create a temporary directory
Add this to the boot method in **app/Providers/AppServiceProvider.php**:

```php
if (! is_dir(config('view.compiled'))) {
    mkdir(config('view.compiled'), 0755, true);
}
```
   

## Todo
<a name="todo"/>

- add queue error / retry  handling
- use bref as a docker container 
- add db password rotation rotation 

## Credits
<a name="credits"/>
  
* A big thank you goes to Tylor Otwell, who with his framework Laravel and services such as [Vapor](https://vapor.laravel.com/) manages to let us let our technological creativity run free.    
* The creators of the php lambda layers called [bref](https://bref.sh/docs/)
* This post [Migration Guide: Serverless + Bref + Laravel](https://medium.com/no-deploys-on-friday/migration-guide-serverless-bref-laravel-fbb513b4c54b) by Thiago Marini
