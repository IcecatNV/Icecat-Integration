<?php

namespace IceCatBundle\Controller;

use Google\Service\Forms\Info;
use IceCatBundle\Services\AccountService;
use IceCatBundle\Services\InfoService;
use MatthiasMullie\Minify\JS;
use Pimcore\Controller\FrontendController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class AccountController extends FrontendController
{

    /**
     * @Route("/icecat/account-update", name="icecat_account_update", options={"expose"=true})
     */
    public function updateUserInfo(Request $request, AccountService $accountService, InfoService $infoService)
    {

        $icecatUserId =  $infoService->getIceCatUserId();
        if (empty($icecatUserId)) {
            return new JsonResponse(['success' => false, "message" => "Authentication failed"]);
        }
        $data =  $request->request->all();
        $accountService->updateUserInfo($icecatUserId, $data);
        return new JsonResponse(['success' => true, "message" => "User updated"]);
    }
}
