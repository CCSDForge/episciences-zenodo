<?php

namespace App\Controller;

use App\Service\OauthClient;
use App\Service\UploadFile;
use App\Service\ZenodoClient;
use League\OAuth2\Client\Grant\AuthorizationCode;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Annotation\Route;
use GuzzleHttp\Client as guzzleClient;
use App\Form\DepositFormType;
use Symfony\Component\Security\Core\Security;
use App\Repository\LogUserActionRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use GuzzleHttp\TransferStats;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class DepositController extends AbstractController
{

    public function new(Request $request, Security $security, LogUserActionRepository $logRepo, ZenodoClient $zenodoClient, UploadFile $uploadFile,LoggerInterface $logger, RequestStack $requestStack, OauthClient $oauthClient): Response
    {
        // token from CAS
        $userInfo = $security->getToken()->getAttributes();
        $form = $this->createForm(DepositFormType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $oauthSession = $requestStack->getSession()->get('access_token',[]);
            if (empty($oauthSession)){
                return $this->redirectToRoute('oauth_login');
            }
            $token = $oauthSession->getToken();
            $oauthClient->checkTokenValidity();
            $deposit = $form->getData();
            $depositFile = $form->get('depositFile')->getData();
            // check if publish directly without file
            if ($form->getClickedButton() && 'save_publish' === $form->getClickedButton()->getName() && (empty($depositFile))) {
                $this->addFlash('error', 'If you want to publish directly you need to upload at least one file');
            } else {
                if ($depositFile) {
                    $uploadFile->uploadFileLocally($this->getParameter('deposit_upload_directory'), $depositFile);
                }
                $emptyDeposit = $zenodoClient->createEmptyDeposit($token);
                if ($emptyDeposit->getStatusCode() === 201){
                    $depositFile = $form->get('depositFile')->getData();
                    $tmpResponse = $emptyDeposit->getBody()->getContents();
                    // Recuperation du bucket pour l'ajout d'un fichier
                    $idDeposit = json_decode($tmpResponse,true)['id'];
                    if(!is_null($depositFile)) {
                        $zenodoClient->postFileInDeposit($depositFile,$tmpResponse,$token,$this->getParameter('deposit_upload_directory'));
                    }
                    $postMetadatas = $zenodoClient->postMetadataInDeposit($deposit,$idDeposit,$token);
                    if ($postMetadatas->getStatusCode() === 200) {
                        $action = 'save';
                        if ($form->getClickedButton() && 'save_publish' === $form->getClickedButton()->getName()) {
                            $publishDeposit = $zenodoClient->publishDeposit($idDeposit,$token);
                            if ($publishDeposit->getStatusCode() === 202) {
                                $action = 'publish';
                            } else {
                                $action = 'error';
                                $error = $publishDeposit->getBody()->getContents();
                                $logger->debug($error, [
                                    // include extra "context" info in your logs
                                    'context' => 'APICall',
                                ]);
                                //api can return two type of array
                                if (array_key_exists('errors', json_decode($error, true))) {
                                    foreach (json_decode($error, true)['errors'] as $value) {
                                        $this->addFlash('error', $value["message"]);
                                    }
                                } else {
                                    $this->addFlash('error', json_decode($error, true)['message']);
                                }
                            }
                        }
                        //log user action
                        $getDepositInfo = json_decode(file_get_contents('https://sandbox.zenodo.org/api/deposit/depositions/'.$idDeposit.'?access_token='.$token),true);
                        $logInfo = array(
                            'username' => $userInfo['username'],
                            'doi_deposit_fix' => $getDepositInfo['conceptrecid'],
                            'doi_deposit_version' => $getDepositInfo['id'],
                            'date'=> new \DateTime(),
                            'action' => $action
                        );
                        $logRepo->addLog($logInfo);
                        //addlog return true or exception
                        if ($action !== 'error') {
                            ($action === 'publish') ? $this->addFlash('success', "Successfully completed check here all info : https://sandbox.zenodo.org/record/".$idDeposit) : $this->addFlash('success', "Successfully completed check here all info : https://sandbox.zenodo.org/deposit/".$idDeposit);
                        }
                    } else {
                        $error = $postMetadatas->getBody()->getContents();
                        $logger->debug($error, [
                            // include extra "context" info in your logs
                            'context' => 'APICall',
                        ]);
                        //api can return two type of array
                        if (array_key_exists('errors', json_decode($error, true))) {
                            foreach (json_decode($error, true)['errors'] as $value) {
                                $this->addFlash('error', $value["message"]);
                            }
                        } else {
                            $this->addFlash('error', json_decode($error, true)['message']);
                        }
                    }
                } else {
                    $error = $emptyDeposit->getBody()->getContents();
                    $logger->debug($error, [
                        // include extra "context" info in your logs
                        'context' => 'APICall',
                    ]);
                    //api can return two type of array
                    if (array_key_exists('errors', json_decode($error, true))) {
                        foreach (json_decode($error, true)['errors'] as $value) {
                            $this->addFlash('error', $value["message"]);
                        }
                    } else {
                        $this->addFlash('error', json_decode($error, true)['message']);
                    }
                }
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

    public function edit(Request $request, $id, Security $security, ZenodoClient $zenodoClient, LogUserActionRepository $logRepo,  UploadFile $uploadFile, LoggerInterface $logger, RequestStack $requestStack, OauthClient $oauthClient) : Response {
        $userInfo = $security->getToken()->getAttributes();
        $oauthSession = $requestStack->getSession()->get('access_token',[]);
        if (!empty($oauthSession)){
            return $this->redirectToRoute('oauth_login');
        }
        $token = $oauthSession->getToken();
        $oauthClient->checkTokenValidity();
        $token = $oauthSession->getToken();
        $response = $zenodoClient->getDepositById($id,$token);
        if ($response->getStatusCode() === 200) {
            $depositInfo = json_decode($response->getBody()->getContents(),true);
            $originalId = json_decode($response->getBody(), true)['record_id'];
            $fileInfo = $zenodoClient->formatFilesInfoFromDeposit($depositInfo['files']);
            $reformatDepositInfo = $zenodoClient->formatMetadatasFromDeposit($depositInfo);
            $statusDeposit = $depositInfo['submitted'];
            $form = $this->createForm(DepositFormType::class, $reformatDepositInfo);
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                $oauthSession = $requestStack->getSession()->get('access_token',[]);
                if (empty($oauthSession)){
                    return $this->redirectToRoute('oauth_login');
                }
                $oauthClient->checkTokenValidity();
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
                                'access_token'=>$token
                            ],
                            'body' =>  $handle,
                        ]);
                    }
                }
                // ajout des données après l'ajout du fichier (si il y en a un)
                $postMetadata = $zenodoClient->postMetadataInDeposit($deposit,$originalId,$token);
                $idDeposit = json_decode($response->getBody(),true)['id'];
                if ($postMetadata->getStatusCode() === 200) {
                    // Define the action for the log
                    $action = 'save';
                    if ($form->getClickedButton() && 'save_publish' === $form->getClickedButton()->getName()) {
                        $publishDeposit = $zenodoClient->publishDeposit($idDeposit,$token);
                        if ($publishDeposit->getStatusCode() === 201) {
                            $action = 'publish';
                        } else {
                            $error = $publishDeposit->getBody()->getContents();
                            $logger->debug($error, [
                                // include extra "context" info in your logs
                                'context' => 'APICall',
                            ]);
                            //api can return two type of array
                            if (array_key_exists('errors', json_decode($error, true))) {
                                foreach (json_decode($error, true)['errors'] as $value) {
                                    $this->addFlash('error', $value["message"]);
                                }
                            } else {
                                $this->addFlash('error', json_decode($error, true)['message']);
                            }
                        }
                    }
                } else {
                    $error = $postMetadata->getBody()->getContents();
                    $logger->debug($error, [
                        // include extra "context" info in your logs
                        'context' => 'APICall',
                    ]);
                    //api can return two type of array
                    if (array_key_exists('errors', json_decode($error, true))) {
                        foreach (json_decode($error, true)['errors'] as $value) {
                            $this->addFlash('error', $value["message"]);
                        }
                    } else {
                        $this->addFlash('error', json_decode($error, true)['message']);
                    }
                    $action = "error";
                }
                $getDepositInfo = json_decode(file_get_contents('https://sandbox.zenodo.org/api/deposit/depositions/'.$idDeposit.'?access_token='.$token),true);
                $logInfo = array(
                    'username' => $userInfo['username'],
                    'doi_deposit_fix' => $getDepositInfo['conceptrecid'],
                    'doi_deposit_version' => $getDepositInfo['id'],
                    'date'=> new \DateTime(),
                    'action' => $action,
                );
                $statusDeposit = $getDepositInfo['submitted'];
                $logRepo->addLog($logInfo);
                if ($action !== 'error') {
                    ($action === 'publish') ? $this->addFlash('success', "Successfully completed check here all info : https://sandbox.zenodo.org/record/".$idDeposit) : $this->addFlash('success', "Successfully completed check here all info : https://sandbox.zenodo.org/deposit/".$idDeposit);
                }

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
        } else {
            $exceptionMessage = '';
            switch ($response->getStatusCode()){
                case 404:
                    $exceptionMessage = 'Deposit not found be sure you have the right informations';
                    break;
                default:
                    $exceptionMessage = json_decode($response->getBody()->getContents(),true)['message'];
                    break;
            }
            $logger->debug($response->getBody()->getContents(), [
                // include extra "context" info in your logs
                'context' => 'APICall',
            ]);

            return $this->render('zenodoexception/error.html.twig',[
                'statusCode' => $response->getStatusCode(),
                'message' => $exceptionMessage,
                'userInfo' => [
                    'lastname' => $userInfo['LASTNAME'],
                    'firstname' => $userInfo['FIRSTNAME'],
                ]
            ]);
        }
    }

    public function deleteFile (Request $request, Security $security, ZenodoClient $zenodoClient, $id, $fileId, Session $session, RequestStack $requestStack, OauthClient $oauthClient) {
        if ($security->getToken()->getAttributes()){
            $oauthSession = $requestStack->getSession()->get('access_token',[]);
            if (empty($oauthSession)){
                $oauthRoute = $this->generateUrl('oauth_login', [], UrlGeneratorInterface::ABSOLUTE_URL);
                return new JsonResponse([
                    'status' => 403,
                    'message' =>"You're disconnect from Zenodo please follow the link to reconnect and being allowed to make actions ",
                    'link' => $oauthRoute
                ]);
            }
            $token = $oauthSession->getToken();
            $oauthClient->checkTokenValidity();
            $fileInfoSended = json_decode($request->getContent(), true);
            $deposit = $zenodoClient->getDepositById($id,$token);
            if ($deposit->getStatusCode() === 200) {
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
                if ($checkValidFile === true) {
                    $deleteFile = $zenodoClient->deleteFilesFromDeposit($token,$id,$fileId);
                } else {
                    return new JsonResponse([
                        'status' => 404,
                        'message' => 'File not found'
                    ]);
                }
                return new JsonResponse($deleteFile);
            } else {
                $error = $deposit->getBody()->getContents();
                if (array_key_exists('errors', json_decode($error, true))) {
                    foreach (json_decode($error, true)['errors'] as $value) {
                        $this->addFlash('error', $value["message"]);
                    }
                } else {
                    return new JsonResponse('error', json_decode($error, true)['message']);
                }
            }
        } else {
            return new JsonResponse('You must be authentified to make this action', Response::HTTP_FORBIDDEN);
        }
    }
}