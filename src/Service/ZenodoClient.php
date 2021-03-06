<?php

namespace App\Service;

use GuzzleHttp\Client;

class ZenodoClient
{

    public function createEmptyDeposit(string $token) {

        $client = new Client();
        return $client->request('POST','https://sandbox.zenodo.org/api/deposit/depositions',[
            'headers' => [
                'Content-Type' => 'application/json'
            ],
            'query' =>[
                'access_token' => $token
            ],
            'json' => new \stdClass(),
        ]);

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

    public function postMetadataInDeposit($deposit, $idDeposit, $token) {

        $pushMeta =  new Client();
        $metaData = $this->formatMetadatas($deposit);
        $pushMeta = $pushMeta->request('PUT',"https://sandbox.zenodo.org/api/deposit/depositions/".$idDeposit,[
            'query'=> [
                'access_token'=>$token
            ],
            'json' => [
                'metadata'=> $metaData,
            ]
        ]);
    }


    public function formatMetadatas($deposit): array {
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
        return $metaData;
    }

    public function publishDeposit($idDeposit, $token){
        $publish =  new Client();
        $publish = $publish->request('POST',"https://sandbox.zenodo.org/api/deposit/depositions/".$idDeposit."/actions/publish",[
            'query'=> [
                'access_token'=>$token
            ],
        ]);
    }

    public function getDepositById($idDeposit,$token){

        $client =  new Client();
        return $client->request('GET',"https://sandbox.zenodo.org/api/deposit/depositions/".$idDeposit,[
            'headers' => [
                'Content-Type' => 'application/json'
            ],
            'query'=>[
                'access_token'=> $token
            ],

        ]);

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
            $ReformatDepositInfo['publication_type'] = $depositInfo['metadata']['publication_type'];
        }

        return $reformatDepositInfo;

    }

    public function deleteFilesFromDeposit($token,$idDeposit,$fileId):array {

        $client =  new Client();
        $response = $client->request('DELETE',"https://sandbox.zenodo.org/api/deposit/depositions/".$idDeposit."/files/".$fileId,[
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

    public function newVersionDeposit($token,$idDeposit){
        $client =  new Client();
        $response = $client->request('POST',"https://sandbox.zenodo.org/api/deposit/depositions/".$idDeposit."/actions/newversion",[
            'headers' => [
                'Content-Type' => 'application/json'
            ],
            'query'=>[
                'access_token'=> $token
            ],
        ]);

        return [
            'status' => $response->getStatusCode(),
            'message' => $response->getReasonPhrase(),
            'idNewVersion' => substr(strrchr(json_decode($response->getBody()->getContents(),true)['links']['latest_draft'], "/"), 1)
        ];
    }
}

