<?php
namespace users;

use Graphene\controllers\Action;
use Graphene\controllers\exceptions\GraphException;
use Graphene\Graphene;

class SendEditMailKey extends Action {

    private static $DEFAULT_TEMPLATE = "Account Reset\n------------\n\n\t- Application name: {{app-name}}\n\t- Editing key: {{editing-key}}\n";

    public function run() {
        /*
         * ResetMail:{
         *   email:'',
         *   template:'wellcome to {{app-name}}!\n you can activate by this url: http://url/{{editing-key}}'
         * }
         *
         * */
        $data = $this->request->getData();
        if (!array_key_exists('ResetMail', $data)) throw new GraphException('invalid request', 400);
        $email = $data['ResetMail']['email'];
        //\Log::debug($data);
        //\Log::debug($email);
        $user = new User();
        $user->setEmail($email);
        $user = $user->read();
        if ($user === null) throw new GraphException('User email is not valid, or not found', 400);

        $userContent = $user->generateEditingKey()->getContent();

        $editingKey = $userContent['editingKey'];
        $appName = Graphene::getInstance()->getSettings()['appName'];
        $subject = array_key_exists('subject', $data['ResetMail']) && $data['ResetMail']['subject'] !== null ? $data['ResetMail']['subject'] : $appName;
        $t = array_key_exists('template', $data['ResetMail']) && $data['ResetMail']['template'] !== null ? $data['ResetMail']['template'] : self::$DEFAULT_TEMPLATE;
        $t = str_replace('{{editing-key}}', $editingKey, $t);
        $t = str_replace('{{app-name}}', $appName, $t);

        $sMess = wordwrap($t, 80);
        try {
            mail($email, $subject, $sMess);
        } catch (\Exception $e) {
        }
        $this->send(['User' => $userContent]);
    }
}