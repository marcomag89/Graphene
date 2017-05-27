<?php
namespace users;

use Graphene\controllers\Filter;

class UserCheck extends Filter {
    private static $cache;

    public function run() {
        if (self::$cache === null) self::$cache = [];
        $session = $this->request->getContextPar('session');
        if ($session !== null && $session['user'] !== null) {
            $user = $this->loadUser($session['user']);
            $this->request->setContextPar('user', $user);
        } else {
            $this->request->setContextPar('user', null);
        }
    }

    private function loadUser($user) {
        if (array_key_exists($user, self::$cache)) self::$cache[$user];
        else {
            try {
                $res = $this->forward('/users/user/' . $user);
                self::$cache[$user] = $res->getData()['User'];
            } catch (\Exception $e) {
                self::$cache[$user] = null;
            }
        }
        return self::$cache[$user];
    }
}