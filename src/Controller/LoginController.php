<?php

namespace App\Controller;

use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\RedirectResponse;

class LoginController extends AbstractController
{

    /**
     * @param Request $request
     * @return RedirectResponse
     */
    #[Route('/login', name: 'login')]
    public function login(Request $request) : RedirectResponse {

        $target = urlencode($this->getParameter('cas_login_target'));
        $url = 'https://'
            . $this->getParameter('cas_host') . $this->getParameter('cas_path')
            . '/login?service=';
        return $this->redirect($url . $target .'/force');
    }
    #[Route('/force', name: 'force')]
    public function force(Request $request, LoggerInterface $logger) {
        $logger->info("USER INFO AFTER FORCE", [$this->container->get('security.token_storage')->getToken()->getAttributes()]);
        return $this->redirect($this->generateUrl('oauth_login'));
    }
    /**
     * @return void
     */
    #[Route('/logout', name: 'logout')]
    public function logout() {
        if (($this->getParameter('cas_logout_target') !== null) && (!empty($this->getParameter('cas_logout_target')))) {
            \phpCAS::logoutWithRedirectService($this->getParameter('cas_logout_target'));
        } else {
            \phpCAS::logout();
        }
    }
}
