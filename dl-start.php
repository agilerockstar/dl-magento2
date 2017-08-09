<?php

require 's3/aws-autoloader.php';
use Aws\S3\S3Client;

$s3Client = new S3Client([
    'version'     => 'latest',
    'region'      => 'eu-central-1',
    'credentials' => [
        'key'    => '***',
        'secret' => '***',
    ],
]);

//If you can download a file more than once
$boolAllowMultipleDownload = 0;

//connect to the DB
$resDB = mysqli_connect("localhost", "***", "***");
mysqli_select_db($resDB, "***");

if(strlen($_GET['key'])==32 and preg_match("/[0-9a-f]{32}/",$_GET['key'])){
	//check the DB for the key
	$resCheck = mysqli_query($resDB, "SELECT * FROM downloads WHERE downloadkey = '".mysqli_real_escape_string($resDB,$_GET['key'])."' LIMIT 1");
	$arrCheck = mysqli_fetch_assoc($resCheck);
	if(!empty($arrCheck['filename'])){
		//check that the download time hasnt expired
		if($arrCheck['expires']>=time()){
			if(!$arrCheck['downloads'] OR $boolAllowMultipleDownload){
				//everything is hunky dory - go ahead				
				
				$cmd = $s3Client->getCommand('GetObject', [
    				'Bucket' => $arrCheck['bucket'],
    				'Key'    => $arrCheck['filename']
				]);
	
				//update the DB to say this file has been downloaded
				mysqli_query($resDB, "UPDATE downloads SET downloads = downloads + 1 WHERE downloadkey = '".mysqli_real_escape_string($resDB,$_GET['key'])."' LIMIT 1");
					
				$request = $s3Client->createPresignedRequest($cmd, '+60 seconds');

				// Get the actual presigned-url
				$presignedUrl = (string) $request->getUri();

				// Get the file and die
				header('Location: '.$presignedUrl);
				exit();
					
			}else{
				//this file has already been downloaded and multiple downloads are not allowed
				echo "Il file &egrave; gi&agrave stato prelevato.";
			}
		}else{
			//this download has passed its expiry date
			echo "Il link non &egrave; pi&ugrave valido.";
		}
	}else{
		//the download key given didnt match anything in the DB
		echo "Il file richiesto non &egrave; disponibile.";
	}
}else{
	//No download key wa provided to this script
	echo "La chiave fornita non &egrave; valida.";
}

?>