<?php

namespace App\tests\Service;

use GuzzleHttp\Client;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use App\Service\ZenodoClient;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ZenodoClientTest extends KernelTestCase
{
    /**
     * @test
     * @return void
     */

    public function formatMetadatasTestWithoutAffi() {

            self::bootKernel();

            $container = static::getContainer();

            $zenodoClient = $container->get(ZenodoClient::class);
            $formatMetaData = $zenodoClient->formatMetadatas($this->formdepositProviderSimple());
            $this->assertIsArray($formatMetaData);
            $this->assertArrayHasKey('title', $formatMetaData );
            $this->assertArrayHasKey('name', $formatMetaData['creators'][0]);
            $this->assertArrayNotHasKey('affiliation', $formatMetaData['creators'][0]);
    }

    /**
     * @test
     * @return void
     */

    public function formatMetadatasTestRichAuthor() {

        self::bootKernel();

        $container = static::getContainer();

        $zenodoClient = $container->get(ZenodoClient::class);
        $formatMetaData = $zenodoClient->formatMetadatas($this->formdepositProviderRichAuthor());
        $this->assertIsArray($formatMetaData);
        $this->assertEquals('dataset', $formatMetaData['upload_type']);
        $this->assertArrayNotHasKey('publication_type', $formatMetaData);
        $this->assertArrayHasKey('name', $formatMetaData['creators'][0]);
        $this->assertArrayHasKey('affiliation', $formatMetaData['creators'][1]);
        $this->assertArrayHasKey('orcid', $formatMetaData['creators'][1]);
    }

    /**
     * @test
     * @return void
     */

    public function formatFilesInfoFromDepositTest() {

        self::bootKernel();

        $container = static::getContainer();

        $zenodoClient = $container->get(ZenodoClient::class);

        $formatMetaData = $zenodoClient->formatFilesInfoFromDeposit($this->depositExempleProvider()['files']);
        $this->assertIsArray($formatMetaData);

        unset($formatMetaData);

        $formatMetaData = $zenodoClient->formatFilesInfoFromDeposit([]);
        $this->assertIsArray($formatMetaData);
        $this->assertArrayNotHasKey('filename',$formatMetaData);
    }

    public function formdepositProviderSimple(): array {

        return [
            "title" => "this is a test",
            'upload_type' => 'publication',
            'publication_type' => 'book',
            'description' => "this is the test <p> test </p>",
            'date' => new \dateTime('now'),
            'author' => [
                0 => ['creator' => 'jch', 'affiliation'=>null, 'orcid'=>null]
            ]
        ];
    }

    public function formdepositProviderRichAuthor(): array {

        return [
            "title" => "this is a test",
            'upload_type' => 'dataset',
            'description' => "this is the test <p> test </p>",
            'date' => new \dateTime('now'),
            'author' => [
                0 => ['creator' => 'jch', 'affiliation'=> null, 'orcid'=> null],
                1 => ['creator' => 'rto', 'affiliation'=>'CCSD', 'orcid'=>'6666-9999-8888-7777']
            ]
        ];

    }

    public function depositExempleProvider(): array {

        return json_decode(
    '{
    "conceptdoi":"10.5072/zenodo.969037",
   "conceptrecid":"969037",
   "created":"2021-12-13T15:45:42.131578+00:00",
   "doi":"10.5072/zenodo.981629",
   "doi_url":"https://doi.org/10.5072/zenodo.981629",
   "files":[
      {
         "checksum":"2831958e6dee47323655531c2cbcc007",
         "filename":"Coffee ipsum.txt",
         "filesize":4749,
         "id":"330ac4b6-7d21-4766-af83-6a3c327e394c",
         "links":{
            "download":"https://sandbox.zenodo.org/api/files/24b80be4-be01-4568-9eee-112961be66ea/Coffee%20ipsum.txt",
            "self":"https://sandbox.zenodo.org/api/deposit/depositions/981629/files/330ac4b6-7d21-4766-af83-6a3c327e394c"
         }
      },
      {
         "checksum":"f413d83801fc84e4f919a8d763974b89",
         "filename":"the_essential_of_lorem ipsum (3e copie).txt",
         "filesize":7149,
         "id":"5e1fb0ad-c3a3-4665-ab25-c80c7bbffd5e",
         "links":{
            "download":"https://sandbox.zenodo.org/api/files/24b80be4-be01-4568-9eee-112961be66ea/the_essential_of_lorem%20ipsum%20%283e%20copie%29.txt",
            "self":"https://sandbox.zenodo.org/api/deposit/depositions/981629/files/5e1fb0ad-c3a3-4665-ab25-c80c7bbffd5e"
         }
      }
   ],
   "id":981629,
   "links":{
      "badge":"https://sandbox.zenodo.org/badge/doi/10.5072/zenodo.981629.svg",
      "bucket":"https://sandbox.zenodo.org/api/files/24b80be4-be01-4568-9eee-112961be66ea",
      "conceptbadge":"https://sandbox.zenodo.org/badge/doi/10.5072/zenodo.969037.svg",
      "conceptdoi":"https://doi.org/10.5072/zenodo.969037",
      "discard":"https://sandbox.zenodo.org/api/deposit/depositions/981629/actions/discard",
      "doi":"https://doi.org/10.5072/zenodo.981629",
      "edit":"https://sandbox.zenodo.org/api/deposit/depositions/981629/actions/edit",
      "files":"https://sandbox.zenodo.org/api/deposit/depositions/981629/files",
      "html":"https://sandbox.zenodo.org/deposit/981629",
      "latest":"https://sandbox.zenodo.org/api/records/981629",
      "latest_html":"https://sandbox.zenodo.org/record/981629",
      "newversion":"https://sandbox.zenodo.org/api/deposit/depositions/981629/actions/newversion",
      "publish":"https://sandbox.zenodo.org/api/deposit/depositions/981629/actions/publish",
      "record":"https://sandbox.zenodo.org/api/records/981629",
      "record_html":"https://sandbox.zenodo.org/record/981629",
      "registerconceptdoi":"https://sandbox.zenodo.org/api/deposit/depositions/981629/actions/registerconceptdoi",
      "self":"https://sandbox.zenodo.org/api/deposit/depositions/981629"
   },
   "metadata":{
      "access_right":"open",
      "communities":[
         {
            "identifier":"zenodo"
         }
      ],
      "creators":[
         {
            "affiliation":"Coffee",
            "name":"J,ch"
         }
      ],
      "description":"<p>coffee</p>",
      "doi":"10.5072/zenodo.981629",
      "license":"CC0-1.0",
      "prereserve_doi":{
         "doi":"10.5072/zenodo.981629",
         "recid":981629
      },
      "publication_date":"2019-05-03",
      "title":"cofee",
      "upload_type":"dataset",
      "version":"2"
   },
   "modified":"2021-12-13T15:46:58.309928+00:00",
   "owner":101102,
   "record_id":981629,
   "state":"done",
   "submitted":true,
   "title":"cofee"
}', true);
    }

}

