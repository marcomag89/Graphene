<?php
namespace profiles;

use Graphene\controllers\Action;
use Graphene\Graphene;

class ActionOk extends Action {
    public function run() {
        $logger = Graphene::getLogger('profile_action');
        $logger->info("Hi! action");
    }
}