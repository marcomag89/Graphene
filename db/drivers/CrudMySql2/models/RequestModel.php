<?php
namespace Graphene\db\drivers\mysql;


use Graphene\db\drivers\MySqlTypes;
use Graphene\models\Model;
use Graphene\utils\Strings;

class RequestModel {
    private $flatValues, $flatTypes, $modelTableName, $structure, $content, $domain, $convertedFlatTypes, /**
     * @var ConnectionManager
     */
        $connectionManager;

    /**
     * @param                   $structure
     * @param                   $content
     * @param                   $domain
     * @param ConnectionManager $connection
     */
    public function __construct($structure, $content, $domain, $connection) {
        $this->connectionManager = $connection;
        $this->structure = $structure;
        $this->content = $content;
        $this->domain = $domain;
        $this->name = $this->parseModelName($domain);
        $this->flatValues = $this->contentToFlatArray($this->content);
        $this->flatTypes = $this->contentToFlatArray($this->structure);

        $this->convertedFlatTypes = MySqlTypes::convertFlatStructTypes($this->flatTypes);
        $this->modelTableName = strtolower(str_replace('.', '_', $domain) . '_model');
    }

    private function parseModelName($domain) {
        $expl = explode('.', $domain);
        $name = $expl[(count($expl) - 1)];

        //echo "\n".$domain.' : '.$name;
        return $name;
    }

    private function contentToFlatArray($content, &$path = '', &$schema = null) {
        if ($schema == null) {
            $schema = [];
        }
        if ($content != null) {
            foreach ($content as $key => $value) {
                if (strcmp($path, '') == 0) {
                    $tmpPath = $key;
                } else {
                    $tmpPath = $path . '_' . $key;
                }

                if (is_array($value) && $content != null) {
                    $this->contentToFlatArray($value, $tmpPath, $schema);
                } else {
                    $schema[$tmpPath] = $value;
                }
            }
        }

        return $schema;
    }

    public static function treeFromFlat($rows) {
        $res = [];
        foreach ($rows as $k => $v) {
            $expl = explode('_', $k);
            $tRes = &$res;
            if (count($expl) > 1) {
                // goto leaf
                foreach ($expl as $e) {
                    if (!isset($tRes[$e])) {
                        $tRes[$e] = [];
                    }
                    $tRes = &$tRes[$e];
                }
                // Popolate leaf
                $tRes = $v;
            } else {
                $tRes[$k] = $v;
            }
        }

        return $res;
    }

    public function getName() {
        return $this->name;
    }

    public function getStructure() {
        return $this->struct;
    }

    public function getContent() {
        return $this->content;
    }

    public function getDomain() {
        return $this->domain;
    }

    public function getFlatDbValues() {
        $ret = [];
        foreach ($this->getFlatValues() as $key => $value) {
            //Bool fix
            try {
                if (Strings::contains($this->flatTypes[$key], Model::BOOLEAN) && $value !== null) {
                    $value = $value ? '1' : '0';
                }
                if ($value === null) {
                    $ret[$key] = 'NULL';
                } else {
                    $ret[$key] = $this->connectionManager->getConnection()->quote($value);
                }
            } catch (\Exception $e) {
                Graphene::getlogger()->error($this->flatTypes);
                Graphene::getlogger()->error($this->getFlatValues());
                throw $e;
            }
        }

        return $ret;
    }

    public function getFlatValues() {
        return $this->flatValues;
    }

    public function getFlatColumnsDbTypes() {
        return $this->convertedFlatTypes;
    }

    public function getModelTableName() {
        return $this->modelTableName;
    }

    public function getSearchableFields() {
        return $this->getFieldsByType(Model::SEARCHABLE);
    }

    public function getFieldsByType($fieldType) {
        $cols = $this->getFlatTypes();
        $ret = [];
        foreach ($cols as $col => $type) {
            if (in_array(substr($fieldType, strlen(Model::CHECK_SEP)), explode(Model::CHECK_SEP, $type))) {
                $ret[] = $col;
            }
        }

        return $ret;
    }

    public function getFlatTypes() {
        return $this->flatTypes;
    }

    public function getUniques() {
        return $this->getFieldsByType(Model::UNIQUE);
    }

    public function haveField($needle) {
        $vals = $this->getFlatTypes();
        foreach ($vals as $field => $value) {
            if (strtolower($needle) === strtolower($field)) {
                return true;
            }
        }

        return false;
    }

    private function colsToJsonArr($row, $json) {
        $res = [];
        $struct = json_decode($json, true)['struct'];

        foreach ($row as $k => $v) {
            $expl = explode('_', $k);
            $tRes = &$res;
            $tStruct = &$struct;
            if (count($expl) > 1) {
                // goto leaf
                foreach ($expl as $e) {
                    if (!isset($tRes[$e])) {
                        $tRes[$e] = [];
                        $tStruct = &$struct[$e];
                    }
                    $tRes = &$tRes[$e];
                }
                // Popolate leaf
                $tRes = $v;
            } else {
                if (Strings::contains($tStruct[$k], Model::DATETIME) && $v === '0000-00-00 00:00:00') {
                    $v = null;
                } else if (Strings::contains($tStruct[$k], Model::BOOLEAN)) {
                    if ($v === 1 || $v === '1') {
                        $v = true;
                    } else {
                        $v = false;
                    }
                } else if (Strings::contains($tStruct[$k], Model::INTEGER) && ($v !== null || $v !== '')) {
                    $v = intval($v);
                } else if (Strings::contains($tStruct[$k], Model::DECIMAL) && ($v !== null || $v !== '')) {
                    $v = floatval($v);
                }

                $tRes[$k] = $v;
            }
        }

        return $res;
    }
}