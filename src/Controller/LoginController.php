<?php

namespace App\Controller;

use App\Form\LoginFormType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use GuzzleHttp\Client as guzzleClient;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;

class LoginController extends AbstractController
{

    public function loginAction(Request $request) : RedirectResponse
    {
        $target = urlencode($this->getParameter('cas_login_target'));
        $url = 'http://'
            . $this->getParameter('cas_host')
            . '/login?service=';



        return $this->redirect($url . $target . '/'.$request->getLocale().'/force');
    }

    public function force(Request $request)
    {
        return $this->redirectToRoute('oauth_login');
    }

    /**
     * @Route("/logout", name="logout")
     */
    public function logoutAction(Request $request)
    {
        if (array_key_exists('casLogoutTarget', $this->getParameter('cas'))) {
            \phpCAS::logoutWithRedirectService($this->getParameter('cas')['casLogoutTarget'] .'/'.$request->getLocale(). '/home');
        } else {
            \phpCAS::logout();
        }
    }

}
