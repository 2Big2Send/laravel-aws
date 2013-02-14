laravel-aws
===========

AWS PHP SDK2 implementation as a Laravel Bundle

##Installation
Add the bundle code to your application/bundle.php file
```php
'laravel-aws' => array('auto' => true),
```

From the laravel-aws/config folder copy aws.php to your application/config folder and update the settings inside.

## Usage
```php
$objectsInBucket = AWSClient::S3ListObjects(array('Bucket'=>'YourBucketName'));
$ec2Instances = AWSClient::EC2DescribeInstances();
```
