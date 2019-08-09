# Serverless Laravel Boilerplate 

- dynamoDB as session driver
- dynamoDB as cache driver
- sqs as queue
- sqs is triggeres a lambda function, whichs executes the message
- s3 as storage provider
- rds aurora serverless mysql 5.6 as database
- schedule:run is triggered every minute
- no warm up time, because of the minutely schedule events
- all resources a deployed within a VPC
- rds can be accessed via a deployed bastion host
- all cloud resources are used natively with iam policies 


- only the bastion host costs around 6$ a month

## AWS A prerequisites
- create a aws web console > ec2 > keypair with the name exporo-slsl-laravel

$aws configure   
$npm install -g serverless   
$npm install  
$composer install   
$application/composer install  
$serverless deploy --stage {stage}  
$serverless invoke -f artisan --data '{"cli":"migrate --force"}' --stage {stage}    

## Local deployment

$docker-compose up -d
$docker-compose exec webapp bash

### TODO
- use bref inside of the docker env? 
- add db password rotation rotation 


## Demo application 

## Prepare own laravel application 

* composer require bref/bref


### bootstrap/app.php

add this line:
$app->useStoragePath($_ENV['APP_STORAGE'] ?? $app->storagePath());

### config/cache.php:
remove key and secret

'dynamodb' => [
            'driver' => 'dynamodb',
            'key' => '',
            'secret' => '',
            'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
            'table' => env('DYNAMODB_CACHE_TABLE', 'cache'),
            'endpoint' => env('DYNAMODB_ENDPOINT'),
        ],

### config/cache.php:
remove key and secret

'sqs' => [
            'driver' => 'sqs',
            'key' => '',
            'secret' => '',
            'prefix' => env('SQS_PREFIX', 'https://sqs.us-east-1.amazonaws.com/your-account-id'),
            'queue' => env('SQS_QUEUE', 'your-queue-name'),
            'region' => env('SQS_REGION', 'us-east-1'),
        ],

### config/filesystems.php:
remove key and secret
 's3' => [
            'driver' => 's3',
            'key' => '',
            'secret' => '',
            'region' => env('AWS_DEFAULT_REGION'),
            'region' => env('SQS_REGION'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
        ],
        
        
Thanks to
https://medium.com/no-deploys-on-friday/migration-guide-serverless-bref-laravel-fbb513b4c54b