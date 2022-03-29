<?php

namespace App\Service;

use GuzzleHttp\Client;
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
                    'Content-Type' => 'application/json'
                ],
            ]);
            $episcienceJournal = [];
            foreach (json_decode($journals->getBody()->getContents(),true) as $value) {
                $episcienceJournal[] = [
                    'title' => $value[1]['Title'],
                    'address' => $value[4]['Address'],
                    'Accepted-repositories'=> $value[5]['Accepted-repositories'],
                ];
            }
            return $episcienceJournal;
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            return $e->getResponse();
        }
    }

    public function formatJournalsForForm(){

        $journals = $this->getJournals();
        $journalArray = [];
        foreach ($journals as $value){
            if (in_array('Zenodo', $value['Accepted-repositories'], true)) {
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