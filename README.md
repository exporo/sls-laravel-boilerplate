# Exporo Serverless Laravel Boilerplate   

##### Table of Contents  
[Summary](#summary)  
[Requirements](#requirements)  
[Installation](#installation)  
[Deployment](#deployment)  
[Local development](#local)  
[Migrate your application](#migration)  
[Credits](#credits)  


## Summary
<a name="summary"/>

We are currently developing a boilerplate for hosting a typical Laravel application serverless  in the AWS Cloud. Therefore we have combined the serverless.com framework, the bref AWS Lambda layers and some AWS Cloudformation scripts. All AWS resources were written as Infrastructure as a Code and being used natively without touching any passwords and secret by hand.

All resources are defined as a cloudformation template in the serverless.yml file. 

* AWS DynamoDB as  a Session driver
* AWS DynamoDB as a Cache driver
* AWS RDS Aurora serverless MySQL 5.6 as a Database
* AWS S3 as a Storage provider
* AWS Lambda event for triggering the cron jobs
* AWS SQS + Lambda Event for queueing processes
* AWS Cloudwatch Lambda events for keeping the functions warm

All resources were paid in a pay as u go model.

Because all resources were private and hosted in a VPC a EC2 instance is placed as a bastion host. The instance type is xxx and costs about 6 € per month. 

Because of the warum up time for the Lambda functions and the Aurora serverless DB we have configured a AWS Cloudwatch event, which is invoking the website lambda function every 4 minute:

```yml
functions:
  website:
    handler: application/public/index.php
    timeout: 30
    layers:
    - 'arn:aws:lambda:eu-central-1:209497400698:layer:php-73-fpm:9'
    events:
    - http: 'ANY /'
    - http: 'ANY {proxy+}'
    - schedule:
        rate: rate(4 minutes)
```

## Requirements
<a name="requirements"/>

* Node.js 6.x or later
* PHP 7.2 or later
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

This demo application implements different page counters, which uses a SQS Queue to store the hits asynchronously.
A cron job resets all page counter hourly.


## Migrate your application
<a name="migration"/>

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

- use bref inside of the docker env? 
- add db password rotation rotation  ??

## Credits
<a name="credits"/>
   
Thanks to  
Taylor Otwell  
https://medium.com/no-deploys-on-friday/migration-guide-serverless-bref-laravel-fbb513b4c54b