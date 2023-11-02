<?php

namespace App\Service;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\TransferStats;

class EpisciencesClient {

    private string $apiEpiUrl;

    public function __construct($apiEpiUrl){

        $this->apiEpiUrl = $apiEpiUrl;

    }


    public function getJournals(){

        $client = new Client();
        try {
            $journals = $client->request('GET',$this->apiEpiUrl.'/api/journals',[
                'headers' => [
                    'Content-Type' => 'application/json',
                    "accept" => 'application/json'
                ],
            ]);
            $episcienceJournal = [];
            $allJournals = json_decode($journals->getBody()->getContents(),true);

            foreach ($allJournals as $journalInfo) {
                $getSettings = array_flip(array_column($journalInfo['settings'],"setting"));
                if (array_key_exists('repositories',$getSettings)) {

                    $episcienceJournal[] = [
                        'title' => $journalInfo['name'],
                        'address' => "https://".$journalInfo['code'].'.episciences.org',
                        'Accepted-repositories'=> json_decode($journalInfo['settings'][$getSettings['repositories']]['value'],true),
                    ];
                }
            }
            return $episcienceJournal;
        } catch (ClientException $e) {
            return $e->getResponse();
        }
    }

    public function formatJournalsForForm(){

        $journals = $this->getJournals();
        $journalArray = [];
        foreach ($journals as $value){
            if (in_array('4', $value['Accepted-repositories'], true)) {
                $journalArray[$value['title']] =  $value['address'];
            }
        }
        return $journalArray;
    }
    public function checkEpiscienceUrl($urlRequested){

        $journals = $this->getJournals();

        $flagExistingUrl = false;
        foreach ($journals as $value){
           if (in_array($urlRequested, $value, true) === true){
               $flagExistingUrl = true;
               break;
           }
        }
        return $flagExistingUrl;
    }
}