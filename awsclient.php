<?php
use Aws\Common\Aws;
use Aws\Common\Enum\Region;
use Aws\Common\Enum\CannedAcl;
use Aws\DynamoDb\Exception\DynamoDbException;

class AWSClient {

	public static function __callStatic($method, $args=array()) {
		require_once(__DIR__.'/awssdk2/aws.phar');

		// Make a connection to AWS.
		$aws = Aws::factory(array(
			'key'    => Config::get('aws.aws_public'),
			'secret' => Config::get('aws.aws_secret'),
			'region' => Region::EU_WEST_1,
			'ssl.certificate_authority' => __DIR__.'/awssdk2/cacert.pem'
		));

		// Work out the service, method and return Variables. to use - this is horrible :(
		$safe_method = strtoupper($method);
		$aws_service = null;
		$aws_return_vars = null;
		if(substr($method, 0, 2) == "S3") {
			$aws_service = 's3';
			$aws_method = substr($method, 2);
		} elseif(substr($safe_method, 0, 3) == "EC2") {
			$aws_service = 'Ec2';
			$aws_method = substr($method, 3);
		} 
		/*elseif(substr($safe_method, 0, 10) == "CLOUDFRONT") {
			$aws_service = 'CloudFront';
			$aws_method = substr($method, 10);
		} elseif(substr($safe_method, 0, 7) == "ROUTE53") {
			$aws_service = 'Route53';
			$aws_method = ssubstr($method, 7);
		} elseif(substr($safe_method, 0, 7) == "GLACIER") {
			$aws_service = 'Glacier';
			$aws_method = substr($method, 7);
		} 
		*/
		else {
			die("Unknown or Unsupported Service Method: ".$method);
		}

		$aws_method = self::camelise($aws_method);
		$serviceData = array(	's3'=>array('listBuckets'	=> array('Require'=>array(), 'Response' => array('Buckets', 'Owner', 'RequestId')),
											'copyObject'	=> array('Require'=>array('Bucket', 'Key', 'CopySource'), 'Response' => array('ETag', 'LastModified', 'Expiration', 'CopySourceVersionId', 'ServerSideEncryption', 'RequestId')),
											'deleteObject'	=> array('Require'=>array('Bucket', 'Key'), 'Response'=>array('DeleteMarker', 'VersionId', 'RequestId')),
											'deleteObjects'	=> array('Require'=>array('Bucket', 'Objects'), 'Respose'=>array('Deleted','Errors','RequestId')),
											'putObject'		=> array('Require'=>array('Bucket', 'Key', 'Body', 'ACL', 'ContentType'), 'Response'=>array('Expiration', 'ServerSideEncryption', 'ETag', 'VersionId', 'RequestId')),
											'listObjects'	=> array('Require'=>array('Bucket'), 'Response'=>array('Marker', 'Contents', 'Name', 'Prefix','MaxKeys',' IsTruncated', 'CommonPrefixes', 'RequestId')),
										), /* End S3 */

								'Ec2'=>array('describeInstances'=>array('Require'=>array(), 'Response'=>array('Reservations')),

										)
							); // serviceData.
		
		try {

			if(isset($serviceData[$aws_service])) {
				if(isset($serviceData[$aws_service][$aws_method])) {
					$aws_service_data = $serviceData[$aws_service][$aws_method];
					$aws_method_required = $aws_service_data['Require'];
					$aws_response_vars = $aws_service_data['Response'];
				} else {
					throw new Exception("Unknown or unsupported ".$aws_service." method: ".$aws_method);
				}
			} else {
				throw new Exception("Unknown or unsupported AWS Service: ".$aws_service);
			}

			// Check the required arguments are sent.
			if(count($aws_method_required) >0) {
				foreach($aws_method_required as $requiredVar) {
					if(!isset($args[0][$requiredVar])) {
						throw new Exception("Required Variable {".$requiredVar."} was not passed in the input array");
					}
				}
			}
		} catch(Exception $e) {
			die("Laravel-AWS Exception: ".$e->getMessage());
		}

		try {
			$argZero = ((count($args) >0) ? $args[0] : array());
			$client = $aws->get($aws_service);
			$aws_response = $client->$aws_method($argZero);
			$returned_data = array();
			foreach($aws_response_vars as $rVar) {
				$returned_data[$rVar] = $aws_response[$rVar];
			}

			return $returned_data;
		} catch(Exception $e) {
			die("AWS Exception: ".$e->getMessage());
		}
	}

	private static function getAWSServiceData() {

	}

	private static function camelise($string) {
		return lcfirst(preg_replace('/(^|_)(.)/e', "strtoupper('\\2')", strval($string)));
	}

}