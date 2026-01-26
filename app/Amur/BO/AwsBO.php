<?php

namespace App\Amur\BO;

use App\Amur\Bean\ILogger;
use Aws\Sts\StsClient;
use Aws\S3\S3Client;
use Aws\S3\Exception\S3Exception;

class AwsBO {

    private $logger;
    private $bucket;

    public function __construct(ILogger $logger) {
        $this->logger = $logger;
        $this->bucket = config('aws.s3_bucket');
    }

    public function setBucket($bucket) {

        $this->bucket = $bucket;

    }

    public function uploadFile($fileName, $path) {
        
        $options = [
            'version' => 'latest',
            'region' => config('aws.s3_region'),
            'credentials' => ['key' => config('aws.s3_key'), 'secret' => config('aws.s3_secret')]
        ];

        $client = new S3Client($options);

        try {
            $response = $client->putObject(array(
                'Bucket' => $this->bucket,
                'Key'    => $fileName,
                'SourceFile' => $path
            ));

            $objectURL = $response['ObjectURL'];

            $this->logger->info('AwsBO->uploadFile - File uploaded', [$objectURL]);

            return $objectURL;

        } catch(S3Exception $e) {
            $this->logger->error('AwsBO->uploadFile',[$e->getAwsErrorMessage()]);
            return false;
        }
    }

    public function getObjectURI($fileKey, $originalFileName = null) {

        $options = [
            'version' => 'latest',
            'region' => config('aws.s3_region'),
            'credentials' => ['key' => config('aws.s3_key'), 'secret' => config('aws.s3_secret')]
        ];

        $client = new S3Client($options);

        $fields = array();
        $fields['Bucket'] = $this->bucket;
        $fields['Key'] = $fileKey;

        if(!is_null($originalFileName)) {
            $fields['ResponseContentDisposition'] = 'filename=' . $originalFileName;
        }

        try {
            $cmd = $client->getCommand('GetObject', $fields);        
            $request = $client->createPresignedRequest($cmd, '+5 minutes');    
        } catch(S3Exception $e) {
            $this->logger->error('AwsBO->getObjectURI',[$e->getAwsErrorMessage()]);
            return false;
        }
        
        return (string) $request->getUri();
    }

    public function getObject($fileKey) {

        $options = [
            'version' => 'latest',
            'region' => config('aws.s3_region'),
            'credentials' => ['key' => config('aws.s3_key'), 'secret' => config('aws.s3_secret')]
        ];

        $client = new S3Client($options);

        try {
            $response = $client->getObject([
                'Bucket' => $this->bucket,
                'Key'    => $fileKey
            ]);

            return $response['Body'];

        } catch(S3Exception $e) {
            $this->logger->error('AwsBO->getObject',[$e->getAwsErrorMessage()]);
            return false;
        }
    }
}