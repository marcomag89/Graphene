<?php

namespace users;

use Graphene\controllers\Action;
use Graphene\controllers\exceptions\GraphException;
use Graphene\Graphene;
use Graphene\utils\Mailer;

class SendEditMailKey extends Action {

    private static $DEFAULT_TEMPLATE = "Account Reset\n------------\n\n\t- Application name: {{app-name}}\n\t- Editing key: {{editing-key}}\n";

    public function run() {
        $settings = Graphene::getInstance()->getSettings()->getSettingsArray();

        $data = $this->request->getData();
        if (!array_key_exists('ResetMail', $data)) throw new GraphException('invalid request, set "ResetMail" as root', 400);

        //Finding user by email
        $email = $data['ResetMail']['email'];
        $user = new User();
        $user->setEmail($email);
        $user = $user->read();

        $userContent = $user->generateEditingKey()->getContent();
        $updated = $user->update();

        try {
            Mailer::send($userContent['email'], 'Reset password per ' . $userContent['name'] . ' ' . $userContent['surname'],
                file_get_contents($settings['users']['password_reset']['template']),
                $userContent
            );
            $this->send($updated);
        } catch (\Exception $e) {
            Graphene::getLogger('user')->error($e);
        }
    }
}
