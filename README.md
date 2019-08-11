# Serverless Laravel Boilerplate   

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

Session driver
Cache driver
Database
Storage
Cron
Queue Listener

All resources were paid in a pay as u go model.

Because all resources were private and hosted in a VPC a EC2 instance is placed as a bastion host. The instance type is xxx and costs about 6 € per month.

## Requirements
<a name="requirements"/>

lorem ipsum

## Installation
<a name="installation"/>

- create a aws web console > ec2 > keypair with the name exporo-slsl-laravel

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

lorem ipsum

## Migrate your application
<a name="migration"/>

##### 1 Add composer dependencies to your project
...
```console
exporo_sls:~$ application/composer require league/flysystem-aws-s3-v3
exporo_sls:~$ application/composer require bref/bref "^0.5"
```

##### 2 Make storage path configurable
Add this line to **bootstrap/app.php**
...

```php
$app->useStoragePath($_ENV['APP_STORAGE'] ?? $app->storagePath());
...


##### 3 Removing error-causing env variables
Remove key and secret env vars from:
- dynamodb in config/cache.php
- sqs in config/queue.ph
- s3 in config/filesystems.php


For example config/cache.php:
...
```php
'dynamodb' => [
            'driver' => 'dynamodb',
            'key' => '',
            'secret' => '',
            'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
            'table' => env('DYNAMODB_CACHE_TABLE', 'cache'),
            'endpoint' => env('DYNAMODB_ENDPOINT'),
        ],
...


##### 4 Create a temporary directory
Add this to the boot method in **app/Providers/AppServiceProvider.php**:

...
```php
if (! is_dir(config('view.compiled'))) {
    mkdir(config('view.compiled'), 0755, true);
}
...
   

## Todo
<a name="todo"/>

- use bref inside of the docker env? 
- add db password rotation rotation  ??

## Credits
<a name="credits"/>
   
Thanks to  
Taylor Otwell  
https://medium.com/no-deploys-on-friday/migration-guide-serverless-bref-laravel-fbb513b4c54b