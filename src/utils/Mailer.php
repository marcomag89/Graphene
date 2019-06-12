<?php

namespace Graphene\utils;

use Graphene\Graphene;
use Mustache_Engine;
use PHPMailer\PHPMailer\PHPMailer;

/**
 * Created by IntelliJ IDEA.
 * User: marco
 * Date: 2019-06-12
 * Time: 01:00
 */
class Mailer {
    static function send($to, $subject, $template, $data) {
        try {
            $config = Graphene::getInstance()->getSettings()->getSettingsArray()['notifications']['mail'];
            // Preparing mailer
            $mail = new PHPMailer(true);
            $mail->CharSet = 'UTF-8';
            //Server settings
            $mail->IsSMTP();
            $mail->IsHTML(true);
            $mail->SMTPDebug = 2;                             // Enable verbose debug output
            $mail->Host = $config['host'];                    // Specify main and backup SMTP servers
            $mail->SMTPAuth = $config['auth_enabled'];        // Enable SMTP authentication
            $mail->Username = $config['user'];                // SMTP username
            $mail->Password = $config['password'];            // SMTP password
            $mail->SMTPSecure = $config['secure'];            // Enable TLS encryption, `ssl` also accepted
            $mail->Port = $config['port'];                    // TCP port to connect to
            $mail->setFrom($config['from'], $config['appName']);

            //Setting contents
            $mail->addAddress($to);
            $mail->Subject = $subject;

            // processing template
            $m = new Mustache_Engine;
            $mail->Body = $m->render($template, $data);

            $mail->send();
        } catch (\Exception $e) {
            Graphene::getLogger('graphene_mailer')->error($e);
        }
    }
}
