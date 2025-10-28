<?php


namespace App\Http\Api;


use App\Domain\User;
use App\Http\Container;
use App\Http\Panel\InvitationController;

class GenInvite extends Container
{
    private $TOKEN_COMPARE = "ll4haXMfVKB6V03ivM6OJA4k9rYLiY1SfGDv2gJNnlQ4eMfeXVRhZhkmCMktBYJLMxFrHxjJih5XacctWxRgC36ip8rKirJj6U3NcR5DjpkGXcwhV98GPE7frukcH4Za";

    public function __invoke($request, $response)
    {
        $token = $request->getHeader('TOKEN');
        if (sizeof($token) <= 0) {
            exit;
        }

        if ($token[0] != $this->TOKEN_COMPARE) {
            exit();
        }

        $admin = User::where('id', 1)->first();
        $inviteController = new InvitationController(null);
        $inviteObject = $inviteController->generateInvite($admin, 2);
        if($inviteObject) {
           return $response->withJson(['success' => true, 'invite' => $inviteObject->code]);
        }
        return $response->withJson(['success' => false]);
    }
}