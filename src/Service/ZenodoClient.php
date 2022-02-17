<?php

namespace App\Service;

use GuzzleHttp\Client;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

class ZenodoClient
{

    private string $apiZenUrl;

    public function __construct(LoggerInterface $logger, $apiZenUrl)
    {
        $this->apiZenUrl = $apiZenUrl;
        $this->logger = $logger;
    }


    public function createEmptyDeposit(string $token) {
        $client = new Client();
        try {
            return $client->request('POST',$this->apiZenUrl.'/api/deposit/depositions',[
                'headers' => [
                    'Content-Type' => 'application/json'
                ],
                'query' =>[
                    'access_token' => $token
                ],
                'json' => new \stdClass(),
            ]);
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            return $e->getResponse();
        }
    }

    public function postFileInDeposit($file,$deposit,$token,$pathLocalFile) {
        foreach ($file as $fileInfo) {
            $filename = $fileInfo->getClientOriginalName();
            $bucket = array_column(json_decode($deposit,true),'bucket')[0];
            $path = $pathLocalFile;
            $handle = fopen($path.'/'.$filename,'rb');
            $requestFile =  new Client();
            $requestFile = $requestFile->request('PUT',"$bucket/$filename",[
                'query'=> [
                    'access_token'=>$token
                ],
                'body' =>  $handle,
            ]);
        }
    }

    public function postMetadataInDeposit($deposit, $idDeposit, $token, $originalDeposit = []) {
        $pushMeta =  new Client();
        $originalMetadata = [];
        if (!empty($originalDeposit)) {
            $originalMetadata = $originalDeposit['metadata'];
        }
        $metaData = $this->formatMetadatas($deposit,$originalMetadata);
        if (!empty($originalDeposit) && ($originalDeposit['state'] ===  "done" && $originalDeposit['submitted'] === true && $originalMetadata['doi'] !== "")) {
            $this->unlockPublishedDepForEditing($idDeposit, $token);
        }
        try {
            return $pushMeta->request('PUT',$this->apiZenUrl."/api/deposit/depositions/".$idDeposit,[
                'query'=> [
                    'access_token'=>$token
                ],
                'json' => [
                    'metadata'=> $metaData,
                ]
            ]);
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            return $e->getResponse();
        }

    }

    public function unlockPublishedDepForEditing($idDeposit, $token){
        $pushMeta =  new Client();
        try {
            return $pushMeta->request('POST',$this->apiZenUrl."/api/deposit/depositions/".$idDeposit."/actions/edit",[
                'query'=> [
                    'access_token'=>$token
                ],
            ]);
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            return $e->getResponse();
        }
    }

    public function formatMetadatas($deposit, $originalMetadata): array {
        $metaData = array(
            'title' => $deposit['title'],
            'upload_type' => $deposit['upload_type'],
            'publication_date' => date_format($deposit['date'],'Y-m-d'),
            'description' => $deposit['description'],
            'creators'=> array(
            ),
        );
        $counter = 0;
        foreach ($deposit['author'] as $value){
            $metaData['creators'][] = [
                'name' => $value['creator'],
                'affiliation' => $value['affiliation'],
                'orcid'=>$value['orcid']
            ];
            //filter null for the api request
            $metaData['creators'][$counter] = array_filter($metaData['creators'][$counter], static function($v) {
                return $v !== null;
            }, ARRAY_FILTER_USE_BOTH);
            $counter++;
        }
        if ($metaData['upload_type'] === 'publication') {
            $metaData['publication_type'] = $deposit['publication_type'];
        }

        // case of edition of deposit, Zenodo don't update information
        // but erase everything except information sended
        // So we need to overload the information array from zenodo

        if (!empty($originalMetadata)) {
            foreach ($originalMetadata as $key => $value){
                if (array_key_exists($key, $metaData)){
                   $originalMetadata[$key] = $metaData[$key];
                }
            }
            if (array_key_exists("publication_type",$metaData)){
                $originalMetadata['publication_type'] = $metaData['publication_type'];
            }
            return $originalMetadata;
        }
        return $metaData;
    }

    public function publishDeposit($idDeposit, $token) {
        $publish =  new Client();
        try {
            return $publish->request('POST',$this->apiZenUrl."/api/deposit/depositions/".$idDeposit."/actions/publish",[
                'query'=> [
                    'access_token'=>$token
                ],
            ]);
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            return $e->getResponse();
        }
    }

    public function getDepositById($idDeposit,$token) {
        $client =  new Client();
        try {
            return $client->request('GET',$this->apiZenUrl."/api/deposit/depositions/".$idDeposit,[
                'headers' => [
                    'Content-Type' => 'application/json'
                ],
                'query'=>[
                    'access_token'=> $token
                ],
            ]);
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            return $e->getResponse();
        }
    }

    public function formatFilesInfoFromDeposit($depositInfoFiles): array {
        $fileInfo = array();
        if (!empty($depositInfoFiles)) {
            $i = 0;
            foreach ($depositInfoFiles as $info) {
                $fileInfo[$i] = array(
                    'id' => $info['id'],
                    'checksum' => $info['checksum'],
                    'link' => $info['links']['self'],
                    'download' => $info['links']['download']
                );
                $fileInfo[$i]['filename'] = (array_key_exists('filename',$info)) ? $info['filename'] : $info['key'];

                $i++;
            }
        }
        return $fileInfo;
    }

    public function formatMetadatasFromDeposit($depositInfo): array {
        $reformatDepositInfo = array(
            'title' => $depositInfo['title'],
            'description' => $depositInfo['metadata']['description'],
            'upload_type' => $depositInfo['metadata']['upload_type'],
            'date' => new \DateTime($depositInfo['metadata']['publication_date']),
            'author' => [],
        );
        $i = 0;
        //reset index in case author is not sorted
        $depositInfo['metadata']['creators'] = array_values($depositInfo['metadata']['creators']);
        foreach ($depositInfo['metadata']['creators'] as $value){
            $reformatDepositInfo['author'][$i]['creator'] = $value['name'];

            if (array_key_exists('affiliation',$value)){
                $reformatDepositInfo['author'][$i]['affiliation'] = $value['affiliation'];
            }
            if (array_key_exists('orcid',$value)){
                $reformatDepositInfo['author'][$i]['orcid'] = $value['orcid'];
            }
            $i++;

        }

        if (!empty($depositInfo['metadata']['upload_type'] === 'publication')) {
            $reformatDepositInfo['publication_type'] = $depositInfo['metadata']['publication_type'];
        }

        return $reformatDepositInfo;
    }

    public function deleteFilesFromDeposit($token,$idDeposit,$fileId):array {
        $client =  new Client();
        $response = $client->request('DELETE',$this->apiZenUrl."/api/deposit/depositions/".$idDeposit."/files/".$fileId,[
            'headers' => [
                'Content-Type' => 'application/json'
            ],
            'query'=>[
                'access_token'=> $token
            ],

        ]);
        return [
            'status' => $response->getStatusCode(),
            'message' => $response->getReasonPhrase()
        ];

    }

    public function newVersionDeposit($token,$idDeposit) {
        $client =  new Client();
        $response = $client->request('POST',$this->apiZenUrl."/api/deposit/depositions/".$idDeposit."/actions/newversion",[
            'headers' => [
                'Content-Type' => 'application/json'
            ],
            'query'=> [
                'access_token'=> $token
            ],
        ]);
        return [
            'status' => $response->getStatusCode(),
            'message' => $response->getReasonPhrase(),
            'idNewVersion' => substr(strrchr(json_decode($response->getBody()->getContents(),true)['links']['latest_draft'], "/"), 1)
        ];
    }

    public function zenodoFormatedFormError($contentReturned) {

        $this->logger->debug($contentReturned, [
            // include extra "context" info in your logs
            'context' => 'APICall',
        ]);
        //api can return two type of array
        $error = [];
        if (array_key_exists('errors', json_decode($contentReturned, true))) {
            foreach (json_decode($contentReturned, true)['errors'] as $value) {
                $error[] = $value["message"];
            }
        } else {
           $error[] = json_decode($contentReturned, true)['message'];
        }
        return $error;
    }


}