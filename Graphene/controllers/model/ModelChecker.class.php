<?php
namespace Graphene\controllers\model;

use Graphene\models\Model;

class ModelChecker
{

    public function __construct()
    {
        $this->tests = array();
        $this->testIdx = - 1;
    }

    /*
     * -------------------------------
     * Fnzioni di controllo Generali
     * -------------------------------
     */
    public function checkValidStruct($struct, &$node = null)
    {
        if ($node == null)
            $node = $struct;
        foreach ($node as $nk => $nv) {
            if (! $this->isValidLabel($nk)) {
                return false;
            } else 
                if (is_array($nv) && ! $this->checkValidStruct($node, $nv))
                    return false;
        }
        return true;
    }

    /**
     * CONTROLLO CONTENUTO
     * Controlla se il contenuto del model e' conforme alla sua struttura.
     * E' possibile ignorare o modificare il controllo su alcuni valori
     * estendendo il metodo 'check($label,$value)'
     *
     * @param Model $model
     * @param $struct
     * @param bool $lazyCheck
     * @return bool
     */
    public function checkContent(Model $model, $struct, $lazyCheck = false)
    {
        return $this->checkExceededValues($model, $model->getContent(), $struct, $lazyCheck) && $this->checkStructValues($model, $model->getContent(), $struct, $lazyCheck);
    }

    /**
     * CONTROLLO VALORI SUPERFLUI
     * Controlla se i valori del model sono contemplati nella struttura e se sono
     * validi
     *
     * @param Model $model
     * @param null $content
     * @param $struct
     * @param bool $lazyCheck
     * @return bool
     */
    private function checkExceededValues(Model $model, $content = null, $struct, $lazyCheck = false)
    {
        foreach ($content as $ck => $cv) {
            if (is_array($cv) && ! $this->checkExceededValues($model, $cv, $struct[$ck]))
                return false;
            else 
                if ($struct == null)
                    return false;
                else 
                    if (! isset($struct[$ck])) {
                        $this->addError($ck, '(undefinied)', '(undefinied)', 'Undefinied field \'' . $ck . '\' into a ' . $model->getModelName() . ' struct');
                        return false;
                    }
        }
        return true;
    }

    /**
     * CONTROLLO VALORI DELLA STRUTTURA
     * Controlla se i valori del model previsti dalla struttura come not null o
     * come not empty
     * sono effettivamente inseriti
     *
     * @param Model $model
     * @param null $content
     * @param $struct
     * @param bool $lazyCheck
     * @return bool
     */
    private function checkStructValues(Model $model, $content = null, $struct, $lazyCheck = false)
    {
        if ($content == null)
            $content = $model->getContent();
        foreach ($struct as $sk => $sv) {
            if (! isset($content[$sk]))
                $content[$sk] = null;
                // TODO Controllo se array e ha nodo prototipo inferiore
            if (is_array($sv)) {
                if (! $this->checkStructValues($model, $content[$sk], $sv, $lazyCheck))
                    return false;
            } else {
                if (! $this->isValidValue($content[$sk], $sv, $sk, $lazyCheck))
                    return false;
            }
        }
        return true;
    }

    /*
     * -------------------------------
     * Fnzioni di controllo Atomiche
     * -------------------------------
     */
    public function isValidLabel($label)
    {
        if (! preg_match('/^[a-zA-Z]{1}\w*$/', $label) || str_contains($label, '_')) {
            return false;
        } else
            return true;
    }
    // Check value type
    public function isValidValue($val, $type, $label = 'nd', $lazyCheck = false)
    {
        if (! is_string($type)) {
            $this->addError($label, $label . '-definition', Model::STRING, 'invalid field definition: ' . $type);
            return false;
        }
        $expl = explode(Model::CHECK_SEP, $type);
        unset($expl[0]);
        $errs = 0;
        foreach ($expl as $check) {
            $check = '--' . $check;
            $chResult = $this->check($check, $val, $lazyCheck);
            if ($chResult == false) {
                $errs ++;
                $this->addError($label, $check, $type, 'Field \'' . $label . '\':\'' . $val . '\' must be: ' . str_replace(Model::CHECK_SEP, ' ', $check));
            } else 
                if (strcasecmp($chResult, 'und') == 0) {
                    $errs ++;
                    $this->addError($label, $check, $type, 'invalid type: ' . $check . ', for label: ' . $label);
                }
        }
        $res = $errs == 0;
        return $res;
    }

    /**
     * Main check function
     *
     * @param $type
     * @param $val
     * @param bool $noChecks
     * @return bool
     */
    private function check($type, $val, $noChecks = false)
    {
        $test = explode(Model::CHECK_PAR, $type)[0];
        if (preg_match('/' . Model::CHECK_PAR . '/', $type))
            $test .= Model::CHECK_PAR;
        switch ($test) {
            /* Type checkers */
            case Model::BOOLEAN:
                return $this->checkBoolean($val, $type);
            case Model::DECIMAL:
                return $this->checkDouble($val, $type);
            case Model::INTEGER:
                return $this->checkInteger($val, $type);
            case Model::STRING:
                return $this->checkString($val, $type);
            case Model::UID:
                return $this->checkUid($val, $type);
            case Model::DATE:
                return $this->checkDate($val, $type);
            case Model::DATETIME:
                return $this->checkDateTime($val, $type);
            case Model::ENUM:
                return $this->checkEnum($val, $type);
            case Model::MATCH:
                return $this->checkMatch($val, $type);
            
            /* Content checkers */
            case Model::NOT_EMPTY:
                return $noChecks || $this->checkNotEmpty($val, $type);
            case Model::NOT_NULL:
                return $noChecks || $this->checkNotNull($val, $type);
            case Model::MIN_LEN:
                return $noChecks || $this->checkMinLenght($val, $type);
            case Model::MAX_LEN:
                return $noChecks || $this->checkMaxLenght($val, $type);
            default:
                return true;
        }
    }

    /*
     * ----------------------
     * CHECKS FUNCTIONS
     * ----------------------
     */
    private function checkDate($val, $type)
    {
        if ($val == null || strcmp($val, '') == 0)
            return true;
        else {
            sscanf($val, "%d-%d-%d", $y, $m, $d);
            return ((int)$y === 0 && (int)$m === 0 && (int)$d === 0) || checkdate($m, $d, $y);
        }
    }

    private function checkDateTime($val, $type)
    {
        if ($val == null || strcmp($val, '') == 0)
            return true;
        else {
            sscanf($val, "%d-%d-%d %d:%d:%d", $y, $m, $d, $h, $mn, $s);
            return checkdate($m, $d, $y) && (int)$h < 24 && (int)$h >= 0 && (int)$mn < 60 && (int)$mn >= 0 && (int)$s < 60 && (int)$s >= 0;
        }
    }

    private function checkInteger($val, $type)
    {
        return $val === null || is_int($val) || preg_match("/^[0-9]+$/", '' . $val);
    }

    private function checkString($val, $type)
    {
        return $val === null || is_string($val);
    }

    private function checkBoolean($val, $type)
    {
        return $val === null || is_bool($val) || preg_match("/^(0|1)+$/", '' . $val);
    }

    private function checkDouble($val, $type)
    {
        return $val === null || is_double($val) || is_integer($val) || intval($val) || doubleval($val);
    }

    private function checkEnum($val, $type)
    {
        return $val === null || in_array($val, explode(',', explode(Model::CHECK_PAR, $type)[1]));
    }

    private function checkMatch($val, $type)
    {
        return $val === null || preg_match(explode(Model::CHECK_PAR, $type)[1], '' . $val);
    }
    
    // Checks values
    private function checkNotEmpty($val, $type)
    {
        return $val === null || $val !== '';
    }

    private function checkMinLenght($val, $type)
    {
        return $val === null || strlen('' . $val) >= explode(Model::CHECK_PAR, $type)[1];
    }

    private function checkMaxLenght($val, $type)
    {
        return $val === null || strlen($val . '') <= explode(Model::CHECK_PAR, $type)[1];
    }

    private function checkUid($val, $type)
    {
        return $val === null || true;
    }

    private function checkNotNull($val, $type)
    {
        return $val !== null;
    }

    private function addError($field, $type, $tGlobal, $message)
    {
        $errArr = array(
            'mismatchType' => $type,
            'globalType' => $tGlobal,
            'message' => $message
        );
        $this->tests[$this->testIdx][$field][$type] = $errArr;
    }

    public function getLastTestErrors()
    {
        return $this->tests[$this->testIdx];
    }

    public function newTest()
    {
        $this->testIdx ++;
        $this->tests[$this->testIdx] = array();
    }

    private $tests;

    private $testIdx;
}

?>
