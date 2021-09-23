<?php

namespace IceCatBundle\Controller;

use IceCatBundle\Services\DataService;
use Pimcore\Controller\FrontendController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use  IceCatBundle\Services\LoginService;

class LoginController extends FrontendController
{
    /**
     * @Route("/admin/icecat/login", name="icecat_login", options={"expose"=true})
     */
    public function LoginAction(Request $request, LoginService $loginObject, DataService $dataService)
    {
        $username = $request->get('userName');
        $password = $request->get('password');
        $dataService->emptyIceCatSession();
        $response = $loginObject->Authapi($username, $password);
        $response['otherInfo'] = $dataService->getOtherInfo(true);
        return new JsonResponse($response);
    }


    /**
     * @Route("/admin/icecat/get-login-page", name="icecat_get-login-page", options={"expose"=true})
     */
    public function getLoginPage(LoginService $service)
    {
        $service->emptyIceCatSession();
        $result = $service->getLoginStatus();

        $result['otherInfo'] = $service->getOtherInfo(true);
        return $this->render(
            "@IceCat/login/getLoginPage.twig",
            $result
        );
    }

    /**
     * @Route("/admin/icecat/logout-page", name="icecat_get-logout-page", options={"expose"=true})
     */
    public function getLogOutPage(LoginService $service)
    {
        $service->emptyIceCatSession();
        $service->logOutUser();
        $result = $service->getLoginStatus();
        return new JsonResponse($result);
    }

    /**
     * @Route("/admin/icecat/other-info", name="icecat_other-info", options={"expose"=true})
     */
    public function getOtherInfo(LoginService $service)
    {
        $response['otherInfo'] = $service->getOtherInfo(true);
        return new JsonResponse($response);
    }
}
