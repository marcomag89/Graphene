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
    protected $config;
    protected $logger;
    protected $phpMailer;
    protected $appName;

    public function __construct() {
        $this->config = Graphene::getInstance()->getSettings()->getSettingsArray()['notifications']['mail'];
        $this->logger = Graphene::getLogger('graphene_mailer');
        $this->phpMailer = new PHPMailer();
        $this->phpMailer->Debugoutput = function ($str, $level) { Graphene::getLogger('mailer')->debug($str); };
        $this->appName =  Graphene::getInstance()->getSettings()->getSettingsArray()['appName'];
    }

    public function prepare($subject) {
        // Preparing mailer
        $this->phpMailer->SMTPDebug = 2;                           // Enable verbose debug output

        $this->phpMailer->CharSet = 'UTF-8';
        //Server settings
        $this->phpMailer->IsSMTP();
        $this->phpMailer->Host = $this->config['host'];                    // Specify main and backup SMTP servers
        $this->phpMailer->SMTPAuth = $this->config['auth_enabled'];        // Enable SMTP authentication
        $this->phpMailer->Username = $this->config['user'];                // SMTP username
        $this->phpMailer->Password = $this->config['password'];            // SMTP password
        $this->phpMailer->SMTPSecure = $this->config['secure'];            // Enable TLS encryption, `ssl` also accepted
        $this->phpMailer->Port = $this->config['port'];                    // TCP port to connect to
        $this->phpMailer->setFrom($this->config['from'], $this->appName);

        $this->phpMailer->Subject = $subject;
        return $this->phpMailer;
    }

    public function addB64Image($name, $b64) {
        $this->phpMailer->addStringAttachment(base64_decode($b64), $name);
    }

    /**
     * @param $mail {PHPMailer}
     * @param $template
     * @param $data
     * @return mixed
     */
    public function setHtmlBody($body, $data = []) {
        // processing template
        $m = new Mustache_Engine();
        $rBody = $m->render($body, $data);

        $this->phpMailer->IsHTML(true);
        $this->phpMailer->Body = $rBody;

        return $this->phpMailer;
    }

    public function sendTo($address) {
        try {
            $this->phpMailer->addAddress($address);
            $this->phpMailer->send();
        } catch (\Exception $e) {
            $this->logger->error($e);
        }
    }

    static function send($to, $subject, $template, $data) {
        $mailer = new Mailer();
        $mailer->prepare($subject);
        $mailer->setHtmlBody($template, $data);
        $mailer->sendTo($to);
    }
}
