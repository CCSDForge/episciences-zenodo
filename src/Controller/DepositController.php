<?php

namespace App\Controller;

use App\Form\EpisciencesFormType;
use App\Service\EpisciencesClient;
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
use App\Form\DepositFormType;
use Symfony\Component\Security\Core\Security;
use App\Repository\LogUserActionRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
class DepositController extends AbstractController
{

    public function new(Request $request, Security $security, LogUserActionRepository $logRepo, ZenodoClient $zenodoClient, EpisciencesClient $episciencesClient, UploadFile $uploadFile,LoggerInterface $logger, RequestStack $requestStack, OauthClient $oauthClient, TranslatorInterface $translator): Response
    {
        // token from CAS
        $userInfo = $security->getToken()->getAttributes();
        $form = $this->createForm(DepositFormType::class);
        $form->handleRequest($request);
        $doiVersionForEpi = null;
        $conceptIdForEpi = null;
        if ($form->isSubmitted() && $form->isValid()) {
            $oauthSession = $requestStack->getSession()->get('access_token',[]);
            if (empty($oauthSession)){
                return $this->redirectToRoute('oauth_login');
            }
            $oauthClient->checkTokenValidity();
            $token = $oauthSession->getToken();
            $deposit = $form->getData();
            $depositFile = $form->get('depositFile')->getData();
            // check if publish directly without file
            if ($form->getClickedButton() && 'save_publish' === $form->getClickedButton()->getName() && (empty($depositFile))) {
                $this->addFlash('error', $translator->trans('nofilePublish'));
            } else {
                if ($depositFile) {
                    $uploadFile->uploadFileLocally($this->getParameter('deposit_upload_directory'), $depositFile);
                }
                $emptyDeposit = $zenodoClient->createEmptyDeposit($token);
                if ($emptyDeposit->getStatusCode() === 201) {
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
                                $message = $zenodoClient->zenodoFormatedFormError($publishDeposit->getBody()->getContents());
                                $this->flashMessageError($message);
                            }
                        }
                        //log user action
                        $getDepositInfo = json_decode(file_get_contents($this->getParameter('app.API_ZEN_URL').'/api/deposit/depositions/'.$idDeposit.'?access_token='.$token),true);
                        $logInfo = array(
                            'username' => $userInfo['username'],
                            'doi_deposit_fix' => $getDepositInfo['conceptrecid'],
                            'doi_deposit_version' => $getDepositInfo['id'],
                            'date'=> new \DateTime(),
                            'action' => $action,
                            'zen_title'=>$getDepositInfo['metadata']['title']
                        );
                        $logRepo->addLog($logInfo);
                        if ($action !== 'error') {
                            ($action === 'publish') ? $this->addFlash('success', $translator->trans('successSaveOrPublish')." : ".$this->getParameter('app.API_ZEN_URL')."/record/".$idDeposit) : $this->addFlash('success', $translator->trans('successSaveOrPublish')." : ".$this->getParameter('app.API_ZEN_URL')."/deposit/".$idDeposit);
                        }
                        if ($action === 'publish'){
                            $doiVersionForEpi = $getDepositInfo['doi'];
                            $conceptIdForEpi = $getDepositInfo['conceptrecid'];
                        }
                    } else {
                        $message = $zenodoClient->zenodoFormatedFormError($postMetadatas->getBody()->getContents());
                        $this->flashMessageError($message);
                    }
                } else {
                    $message = $zenodoClient->zenodoFormatedFormError($emptyDeposit->getBody()->getContents());
                    $this->flashMessageError($message);
                }
            }
        }
            return $this->renderForm('deposit/index.html.twig', [
                'controller_name' => 'DepositController',
                'form' => $form,
                'userInfo' => [
                    'lastname' => $userInfo['LASTNAME'],
                    'firstname' => $userInfo['FIRSTNAME'],
                ],
                'doiVersionForEpi' => $doiVersionForEpi,
                'conceptIdForEpi' => $conceptIdForEpi
            ]);
    }

    public function edit(Request $request, $id, Security $security, ZenodoClient $zenodoClient, LogUserActionRepository $logRepo,  UploadFile $uploadFile, LoggerInterface $logger, RequestStack $requestStack, OauthClient $oauthClient, TranslatorInterface $translator) : Response {
        $userInfo = $security->getToken()->getAttributes();
        $oauthSession = $requestStack->getSession()->get('access_token',[]);
        if (empty($oauthSession)){
            return $this->redirectToRoute('oauth_login');
        }
        $oauthClient->checkTokenValidity();
        $token = $oauthSession->getToken();
        if (!is_null($logRepo->isExistingDeposit($userInfo['username'],$id))){
            $response = $zenodoClient->getDepositById($id,$token);
            if ($response->getStatusCode() === 200) {
                $doiVersionForEpi = null;
                $conceptIdForEpi = null;
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
                    $token = $oauthSession->getToken();
                    $deposit = $form->getData();
                    $depositFile = $form->get('depositFile')->getData();
                    if ($form->getClickedButton() && 'new_version' === $form->getClickedButton()->getName()) {
                        $newVersion = $zenodoClient->newVersionDeposit($token,$depositInfo['id']);
                        if ($newVersion['status'] === 201) {
                            $logInfo = array(
                                'username' => $userInfo['username'],
                                'doi_deposit_fix' => $newVersion['content']['conceptrecid'],
                                'doi_deposit_version' => $newVersion['idNewVersion'],
                                'date'=> new \DateTime(),
                                'action' => 'new_version',
                                'zen_title'=>$newVersion['content']['metadata']['title']
                            );
                            $logRepo->addLog($logInfo);
                            return $this->redirectToRoute('edit_deposit', ['id' => $newVersion['idNewVersion']]);
                        } else {
                            $this->addFlash('error', "An error occurred".$newVersion['message'] );
                            return $this->redirect($request->getUri());
                        }
                    }
                    if(!is_null($depositFile)) {
                        $uploadFile->uploadFileLocally($this->getParameter('deposit_upload_directory'), $depositFile);
                        $zenodoClient->postFileInDeposit($depositFile,$response->getBody(),$token,$this->getParameter('deposit_upload_directory'));
                    }
                    // ajout des données après l'ajout du fichier (si il y en a un)
                    $postMetadata = $zenodoClient->postMetadataInDeposit($deposit,$originalId,$token,$depositInfo);
                    $idDeposit = json_decode($response->getBody(),true)['id'];
                    if ($postMetadata->getStatusCode() === 200) {
                        // Define the action for the log
                        $action = 'update';
                        if ($form->getClickedButton() && 'save_publish' === $form->getClickedButton()->getName()) {
                            $publishDeposit = $zenodoClient->publishDeposit($idDeposit,$token);
                            if ($publishDeposit->getStatusCode() === 202) {
                                $action = 'publish';
                            } else {
                                $message = $zenodoClient->zenodoFormatedFormError($publishDeposit->getBody()->getContents());
                                $this->flashMessageError($message);
                            }
                        }
                    } else {
                        $message = $zenodoClient->zenodoFormatedFormError($postMetadata->getBody()->getContents());
                        $this->flashMessageError($message);
                        $action = "error";
                    }
                    $getDepositInfo = json_decode(file_get_contents($this->getParameter('app.API_ZEN_URL').'/api/deposit/depositions/'.$idDeposit.'?access_token='.$token),true);
                    $logInfo = array(
                        'username' => $userInfo['username'],
                        'doi_deposit_fix' => $getDepositInfo['conceptrecid'],
                        'doi_deposit_version' => $getDepositInfo['id'],
                        'date'=> new \DateTime(),
                        'action' => $action,
                        'zen_title'=>$getDepositInfo['metadata']['title']
                    );
                    $statusDeposit = $getDepositInfo['submitted'];
                    $logRepo->addLog($logInfo);
                    if ($action !== 'error') {
                        ($action === 'publish') ? $this->addFlash('success', $translator->trans('successSaveOrPublish')." : ".$this->getParameter('app.API_ZEN_URL')."/record/".$idDeposit) : $this->addFlash('success', $translator->trans('successSaveOrPublish')." : ".$this->getParameter('app.API_ZEN_URL')."/deposit/".$idDeposit);
                    }
                    // we need to store in session because we reload the page so we lost this information

                    if ($action === 'publish'){
                        $requestStack->getSession()->set('edit-doi-tmp', $getDepositInfo['metadata']['doi']);
                        $requestStack->getSession()->set('edit-ci-doi-tmp', $getDepositInfo['conceptrecid']);
                    }
                    return $this->redirect($request->getUri());
                } else {
                    $doiVersionForEpi = $requestStack->getSession()->get('edit-doi-tmp');
                    $conceptIdForEpi = $requestStack->getSession()->get('edit-ci-doi-tmp');
                    $requestStack->getSession()->remove('edit-doi-tmp');
                    $requestStack->getSession()->remove('edit-ci-doi-tmp');
                    return $this->renderForm('deposit/edit.html.twig', [
                        'controller_name' => 'DepositController',
                        'form' => $form,
                        'filesInfo' => $fileInfo,
                        'idDeposit' => $id,
                        'DepositPublished' => $statusDeposit,
                        'userInfo' => [
                            'lastname' => $userInfo['LASTNAME'],
                            'firstname' => $userInfo['FIRSTNAME'],
                        ],
                        'doiVersionForEpi'=> $doiVersionForEpi,
                        'conceptIdForEpi' => $conceptIdForEpi
                    ]);
                }
            } else {
                $exceptionMessage = '';
                switch ($response->getStatusCode()) {
                    case 404:
                        $exceptionMessage = $translator->trans('depositNotFound');
                        break;
                    default:
                        $exceptionMessage = json_decode($response->getBody()->getContents(),true)['message'];
                        break;
                }
                $logger->debug($response->getBody()->getContents(), [
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
        }else{
            return $this->render('zenodoexception/error.html.twig',[
                'statusCode' => '403',
                'message' => $translator->trans('blockDeposit'),
                'userInfo' => [
                    'lastname' => $userInfo['LASTNAME'],
                    'firstname' => $userInfo['FIRSTNAME'],
                ]
            ]);
        }
    }

    public function deleteFile (Request $request, Security $security, ZenodoClient $zenodoClient, $id, $fileId, Session $session, RequestStack $requestStack, OauthClient $oauthClient, LoggerInterface $logger, TranslatorInterface $translator) {
        if ($security->getToken()->getAttributes()){
            $oauthSession = $requestStack->getSession()->get('access_token',[]);
            if (empty($oauthSession)){
                $oauthRoute = $this->generateUrl('oauth_login', [], UrlGeneratorInterface::ABSOLUTE_URL);
                return new JsonResponse([
                    'status' => 403,
                    'message' => $translator->trans('zenodoDisconnected'),
                    'link' => $oauthRoute
                ]);
            }
            $oauthClient->checkTokenValidity();
            $token = $oauthSession->getToken();
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
                    $deposit->getBody()->seek(0);
                    $logger->debug('error send : '.$request->getContent().' get '. $deposit->getBody()->getContents(), [
                        // include extra "context" info in your logs
                        'context' => 'APICall Delete File',
                    ]);
                    return new JsonResponse([
                        'status' => 404,
                        'message' => $translator->trans('fileNotFound')
                    ]);
                }
                return new JsonResponse($deleteFile);
            } else {
                $message = $zenodoClient->zenodoFormatedFormError($deposit->getBody()->getContents());
                foreach ($message as $value) {
                    return new JsonResponse([
                        'status' => 403,
                        'message' => $message
                    ]);
                }
            }
        } else {
            return new JsonResponse([
                'status' => 403,
                'message' => $translator->trans('casUnauthorized')
            ]);
        }
    }

    public function listDeposit(Request $request, Security $security, LogUserActionRepository $logRepo){
        $userInfo = $security->getToken()->getAttributes();
        $pagination = $logRepo->getListDepositByUser($userInfo['username'],$request);
        return $this->render('home/list.html.twig',[
            'userInfo' => [
                'lastname' => $userInfo['LASTNAME'],
                'firstname' => $userInfo['FIRSTNAME'],
            ],
            'pagination' => $pagination
        ]);
    }

    private function flashMessageError($message): void {
        foreach ($message as $value){
            $this->addFlash('error',$value);
        }
    }

    public function episcienceLink(Request $request, EpisciencesClient $episciencesClient,Security $security, TranslatorInterface $translator){

        $userInfo = $security->getToken()->getAttributes();
        if (empty($request->query->get('doi'))||empty($request->query->get('ci'))){
            return $this->render('zenodoexception/error.html.twig',[
                'statusCode' => '404',
                'message' => $translator->trans('doiNotFound'),
                'userInfo' => [
                    'lastname' => $userInfo['LASTNAME'],
                    'firstname' => $userInfo['FIRSTNAME'],
                ]
            ]);
        }
        $form = $this->createForm(EpisciencesFormType::class,null,[
            'journals'=>$episciencesClient->formatJournalsForForm(),
            'doi'=>$request->query->get('doi'),
            'ci'=>$request->query->get('ci'),
            'method'=> 'POST',
        ]);
        return $this->renderForm('deposit/linkepi.html.twig',[
            'form' => $form,
            'userInfo' => [
                'lastname' => $userInfo['LASTNAME'],
                'firstname' => $userInfo['FIRSTNAME'],
            ],
            'doi' => $request->query->get('doi')
        ]);
   }
}