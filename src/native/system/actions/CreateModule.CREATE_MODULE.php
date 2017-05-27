<?php
namespace system;
use Graphene\controllers\Action;
use Graphene\Graphene;

class CreateModule extends Action {

    public function run() {
        $url = Graphene::getInstance()->getSettings()['modulesUrl']."";
        $namespace = 'testmod';
        $name      = 'com.test'.$namespace;
        $author    = 'Pippo rossi';

        mkdir ($url."/".$namespace);
        $manifest = array(
            "v"=>"0.1.1",
            "info" => array(
                "version"   => "0.0.0.1",
                "name"      => $name,
                "namespace" => $namespace,
                "author"    => $author,
                "support"   => ''
            ),
            "actions" => array(array(
                "method"  => 'get',
                "query"   => 'hello',
                "name"    => 'HELLO_WORLD',
                "handler" => 'HelloWorld@actions/HelloWorld.HELLO_WORLD.php'
            ))
        );

        $fp = fopen($url."/".$namespace."/manifest.json", 'w');
        fwrite($fp, json_encode($manifest,JSON_PRETTY_PRINT));
        fclose($fp);

        //ADD ACTION
        $head     = "<?php\nnamespace ".$namespace.";\nuse Graphene\\controllers\\Action;\nclass HelloWorld extends Action {\n";
        $content  = "\tpublic function run() {\n\t\t\$this->sendMessage('hello world');\n\t}";
        $end      = "\n}";
        $actionFl = $head.$content.$end;

        if (!file_exists($url."/".$namespace."/actions")) mkdir($url."/".$namespace."/actions");
        $fp = fopen($url."/".$namespace."/actions/HelloWorld.HELLO_WORLD.php", 'w');
        fwrite($fp, $actionFl);
        fclose($fp);

        $this -> sendMessage('module created on: '.$url."/".$namespace);
        //$this->sendMessage($url);
    }
}