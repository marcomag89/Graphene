<?php

namespace Graphene\utils;
/**
 * Created by IntelliJ IDEA.
 * User: Marco
 * Date: 20/09/15
 * Time: 20:59
 */
class Settings
{
    private function __construct($setingsArray)
    {
        $this->settingsArray = $setingsArray;
        /*        if (is_readable(absolute_from_script('settings.json'))) {
                    $this->settingsArray = json_decode(file_get_contents(absolute_from_script('settings.json')), true);
                } else {
                    $errRet = array("error" => array("message" => "settings file not found", "code" => "500"));
                    http_response_code(500);
                    echo json_encode($errRet);
                }*/
    }

    /**
     * @param $key
     * @param null $default
     * @return mixed | null
     */
    public function get($key, $default = null)
    {
        return array_key_exists($key, $this->settingsArray) ? $this->settingsArray[$key] : $default;
    }

    public function getSettingsArray()
    {
        return $this->settingsArray;
    }

    /**
     * @param $settings
     * @return null|Settings
     */
    public static function load($settings)
    {
        $settingsRet = null;
        if (is_array($settings)) {
            $settingsRet = new Settings($settings);
        } else if (is_string($settings) && is_readable(absolute_from_script($settings))) {
            $settingsRet = new Settings(json_decode(file_get_contents(absolute_from_script($settings)), true));
        } else if (is_string($settings) && json_decode($settings) != null) {
            $settingsRet = new Settings(json_decode(file_get_contents(absolute_from_script('settings.json')), true));
        } else {
            $settingsRet = new Settings([]);
        }
        return $settingsRet;
    }

    private $settingsArray;


    //private static $instance = null;
}