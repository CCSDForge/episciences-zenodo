<?php

namespace App\Controller;

use App\Service\UploadFile;
use App\Service\ZenodoClient;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpClient\HttpClient;
use GuzzleHttp\Client as guzzleClient;
use App\Form\DepositFormType;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\String\Slugger\SluggerInterface;
use App\Repository\LogUserActionRepository;
use Symfony\Component\HttpFoundation\JsonResponse;


class DepositController extends AbstractController
{

    public function new(Request $request, SluggerInterface $slugger, Security $security, LogUserActionRepository $logRepo, ZenodoClient $zenodoClient, UploadFile $uploadFile): Response
    {

        // token from CAS
        $userInfo = $security->getToken()->getAttributes();
        //build form
        $form = $this->createForm(DepositFormType::class);
        //Get request
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $deposit = $form->getData();
            $depositFile = $form->get('depositFile')->getData();
            // check if publish directly without file
            if ($form->getClickedButton() && 'save_publish' === $form->getClickedButton()->getName() && (empty($depositFile))) {
                $this->addFlash('error', 'If you want to publish directly you need to upload at least one file');
            } else {
                if ($depositFile) {
                    $uploadFile->uploadFileLocally($this->getParameter('deposit_upload_directory'), $depositFile);
                }
                $emptyDeposit = $zenodoClient->createEmptyDeposit($this->getParameter('app.SBX_TOKEN'));
                $depositFile = $form->get('depositFile')->getData();
                $tmpResponse = $emptyDeposit->getBody()->getContents();
                // Recuperation du bucket pour l'ajout d'un fichier
                $idDeposit = json_decode($tmpResponse,true)['id'];
                if(!is_null($depositFile)) {
                    $zenodoClient->postFileInDeposit($depositFile,$tmpResponse,$this->getParameter('app.SBX_TOKEN'),$this->getParameter('deposit_upload_directory'));
                }
                $zenodoClient->postMetadataInDeposit($deposit,$idDeposit,$this->getParameter('app.SBX_TOKEN'));

                $action = 'save';
                if ($form->getClickedButton() && 'save_publish' === $form->getClickedButton()->getName()) {
                    $zenodoClient->publishDeposit($idDeposit,$this->getParameter('app.SBX_TOKEN'));
                    $action = 'publish';
                }
                //log user action
                $getDepositInfo = json_decode(file_get_contents('https://sandbox.zenodo.org/api/deposit/depositions/'.$idDeposit.'?access_token='.$this->getParameter('app.SBX_TOKEN')),true);
                $logInfo = array(
                    'username' => $userInfo['username'],
                    'doi_deposit_fix' => $getDepositInfo['conceptrecid'],
                    'doi_deposit_version' => $getDepositInfo['id'],
                    'date'=> new \DateTime(),
                    'action' => $action
                );
                //addlog return true or exception
                $flashMessage = ($action === 'publish') ? $this->addFlash('success', "Successfully completed check here all info : https://sandbox.zenodo.org/record/".$idDeposit) : $this->addFlash('success', "Successfully completed check here all info : https://sandbox.zenodo.org/deposit/".$idDeposit);
                $logRepo->addLog($logInfo) ?  $flashMessage :  $this->addFlash('error', 'Something wrong happened');
            }
        }
        return $this->renderForm('deposit/index.html.twig', [
            'controller_name' => 'DepositController',
            'form' => $form,
            'userInfo' => [
                'lastname' => $userInfo['LASTNAME'],
                'firstname' => $userInfo['FIRSTNAME'],
            ]
        ]);
    }

    public function edit(Request $request, $id, Security $security, ZenodoClient $zenodoClient, UploadFile $uploadFile) : Response {

        $userInfo = $security->getToken()->getAttributes();

        $token = $this->getParameter('app.SBX_TOKEN');

        $response = $zenodoClient->getDepositById($id,$token);

        $depositInfo = json_decode($response->getBody()->getContents(),true);

        $originalId = json_decode($response->getBody(), true)['record_id'];


        $fileInfo = $zenodoClient->formatFilesInfoFromDeposit($depositInfo['files']);

        $reformatDepositInfo = $zenodoClient->formatMetadatasFromDeposit($depositInfo);

        $statusDeposit = $depositInfo['submitted'];

        $form = $this->createForm(DepositFormType::class, $reformatDepositInfo);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $deposit = $form->getData();
            $depositFile = $form->get('depositFile')->getData();
            if ($form->getClickedButton() && 'new_version' === $form->getClickedButton()->getName()) {
                $newVersion = $zenodoClient->newVersionDeposit($token,$depositInfo['id']);
                if ($newVersion['status'] === 201 ) {
                    return $this->redirectToRoute('edit_deposit', ['id' => $newVersion['idNewVersion']]);
                } else {
                    $this->addFlash('error', "An error occurred".$newVersion['message'] );
                    return $this->redirect($request->getUri());
                }
            }
            if ($depositFile) {
                // foreach pour prévenir des multiple upload
                $uploadFile->uploadFileLocally($this->getParameter('deposit_upload_directory'), $depositFile);
                foreach ($depositFile as $fileInfo) {
                    $filename = $fileInfo->getClientOriginalName();
                    $path = $this->getParameter('deposit_upload_directory');
                    $handle = fopen($path.'/'.$filename,'rb');
                    $bucket = json_decode($response->getBody(), true)['links']['bucket'];
                    $requestFile =  new guzzleClient();
                    $requestFile = $requestFile->request('PUT',"$bucket/$filename",[
                        'query'=> [
                            'access_token'=>$this->getParameter('app.SBX_TOKEN')
                        ],
                        'body' =>  $handle,
                    ]);
                }
            }

            $pushMeta =  new guzzleClient();

            // ajout des données après l'ajout du fichier (si il y en a un)

            $zenodoClient->postMetadataInDeposit($deposit,$originalId,$token);

            $idDeposit = json_decode($response->getBody(),true)['id'];
            // Define the action for the log
            $action = 'save';
            if ($form->getClickedButton() && 'save_publish' === $form->getClickedButton()->getName()) {
                $zenodoClient->publishDeposit($idDeposit,$this->getParameter('app.SBX_TOKEN'));
                $action = 'publish';
            }
            $getDepositInfo = json_decode(file_get_contents('https://sandbox.zenodo.org/api/deposit/depositions/'.$idDeposit.'?access_token='.$this->getParameter('app.SBX_TOKEN')),true);
            $logInfo = array(
                'username' => $userInfo['username'],
                'doi_deposit_fix' => $getDepositInfo['conceptrecid'],
                'doi_deposit_version' => $getDepositInfo['id'],
                'date'=> new \DateTime(),
                'action' => $action,
            );
            $statusDeposit = $getDepositInfo['submitted'];

            $flashMessage = ($action === 'publish') ? $this->addFlash('success', "Successfully completed check here all info : https://sandbox.zenodo.org/record/".$idDeposit) : $this->addFlash('success', "Successfully completed check here all info : https://sandbox.zenodo.org/deposit/".$idDeposit);
            return $this->redirect($request->getUri());
        } else {

            return $this->renderForm('deposit/edit.html.twig', [
                'controller_name' => 'DepositController',
                'form' => $form,
                'filesInfo' => $fileInfo,
                'idDeposit' => $id,
                'DepositPublished' => $statusDeposit,
                'userInfo' => [
                    'lastname' => $userInfo['LASTNAME'],
                    'firstname' => $userInfo['FIRSTNAME'],
                ]
            ]);
        }
    }

    public function deleteFile (Request $request, Security $security, ZenodoClient $zenodoClient, $id, $fileId, Session $session) {

        if ($security->getToken()->getAttributes()){
            $token = $this->getParameter('app.SBX_TOKEN');
            $fileInfoSended = json_decode($request->getContent(), true);
            $deposit = $zenodoClient->getDepositById($id,$token);
            $depositInfo = json_decode($deposit->getBody()->getContents(),true);
            $checkValidFile = false;

            foreach ($depositInfo['files'] as $key => $value){
                if (in_array($fileInfoSended['checksum'],$depositInfo['files'][$key],true)
                    && (in_array($fileInfoSended['linkFile'],$depositInfo['files'][$key]['links'],true))
                    && (in_array($fileInfoSended['fileName'],$depositInfo['files'][$key],true))
                    && (in_array($fileInfoSended['id'],$depositInfo['files'][$key],true)))  {
                        $checkValidFile = true;
                        break;
                    }
            }
            if ($checkValidFile === true){
                $deleteFile = $zenodoClient->deleteFilesFromDeposit($token,$id,$fileId);
            }
            return new JsonResponse($deleteFile);
        }
        else{
            return new JsonResponse('no results found', Response::HTTP_FORBIDDEN);
        }
    }
}