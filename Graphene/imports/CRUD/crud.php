<?php
namespace imports;

use Graphene\controllers\Action;
use Graphene\models\Model;

class Create extends Action
{

    public function run()
    {
        $model = new $this->pars[0]();
        $model = $model::getByRequest();
        $sModel = $model->create();
        $this->send($sModel);
    }

    public function getResponseStruct(){
        $model = new $this->pars[0]();
        return [$model->getModelName() => $model->getReadActionStruct()];
    }

    public function getRequestStruct(){
        $model = new $this->pars[0]();
        return [$model->getModelName()=> $model->getCreateActionStruct()];
    }

    public function getActionInterface()
    {
        $model = new $this->pars[0]();
        $struct = [$model->getModelName() => $model->getCreateActionStruct()];
        $flatStructArr = $this->contentToFlatArray($struct);
        $flatStruct = [];
        foreach ($flatStructArr as $k => $fieldStruct) {
            $flatStructArr[$k] = explode(Model::CHECK_SEP, $fieldStruct);
            $flatStruct[$k] = [];
            foreach ($flatStructArr[$k] as $check) {
                if ($check !== '') {
                    $flatStruct[$k][] = $check;
                }
            }
        }
        return [
            "name" => "STD_CREATE",
            "struct" => $struct,
            "flat-struct" => $flatStruct
        ];
    }

    private function contentToFlatArray($content, &$path = '', &$schema = null)
    {
        if ($schema == null) $schema = array();
        foreach ($content as $key => $value) {
            if (strcmp($path, '') == 0) $tmpPath = $key;
            else $tmpPath = $path . '_' . $key;

            if (is_array($value) && $content != NULL) $this->contentToFlatArray($value, $tmpPath, $schema);
            else {
                $schema[$tmpPath] = $value;
            }
        }
        return $schema;
    }
}

class Read extends Action{
    public function run()
    {
        $model = new $this->pars[0]();
        $model->setLazy(true);
        $id = $this->request->getPar('id');
        $model->setId($id);
        $readed = $model->read();
        $this->send($readed);
    }

    public function getResponseStruct(){
        $model = new $this->pars[0]();
        return [$model->getModelName() => $model->getReadActionStruct()];
    }

    public function getActionInterface()
    {
        $model = new $this->pars[0]();
        $struct = [$model->getModelName() => $model->getReadActionStruct()];
        $flatStructArr = $this->contentToFlatArray($struct);
        $flatStruct = [];
        foreach ($flatStructArr as $k => $fieldStruct) {
            $flatStructArr[$k] = explode(Model::CHECK_SEP, $fieldStruct);
            $flatStruct[$k] = [];
            foreach ($flatStructArr[$k] as $check) {
                if ($check !== '') {
                    $flatStruct[$k][] = $check;
                }
            }
        }
        return [
            "name" => "STD_READ",
            "struct" => $struct,
            "flat-struct" => $flatStruct
        ];
    }

    private function contentToFlatArray($content, &$path = '', &$schema = null)
    {
        if ($schema == null) $schema = array();
        foreach ($content as $key => $value) {
            if (strcmp($path, '') == 0) $tmpPath = $key;
            else $tmpPath = $path . '_' . $key;

            if (is_array($value) && $content != NULL) $this->contentToFlatArray($value, $tmpPath, $schema);
            else {
                $schema[$tmpPath] = $value;
            }
        }
        return $schema;
    }
}

class Update extends Action
{

    public function run()
    {
        $model = new $this->pars[0]();
        $model = $model::getByRequest();
        $uModel = $model->update();
        $this->send($uModel);
    }

    public function getResponseStruct(){
        $model = new $this->pars[0]();
        return [$model->getModelName() => $model->getReadActionStruct()];
    }

    public function getRequestStruct(){
        $model = new $this->pars[0]();
        return [$model->getModelName() => $model->getUpdateActionStruct()];
    }

    public function getActionInterface()
    {
        $model = new $this->pars[0]();
        $struct = [$model->getModelName() => $model->getUpdateActionStruct()];
        $flatStructArr = $this->contentToFlatArray($struct);
        $flatStruct = [];
        foreach ($flatStructArr as $k => $fieldStruct) {
            $flatStructArr[$k] = explode(Model::CHECK_SEP, $fieldStruct);
            $flatStruct[$k] = [];
            foreach ($flatStructArr[$k] as $check) {
                if ($check !== '') {
                    $flatStruct[$k][] = $check;
                }
            }
        }
        return [
            "name" => "STD_UPDATE",
            "struct" => $struct,
            "flat-struct" => $flatStruct
        ];
    }

    private function contentToFlatArray($content, &$path = '', &$schema = null)
    {
        if ($schema == null) $schema = array();
        foreach ($content as $key => $value) {
            if (strcmp($path, '') == 0) $tmpPath = $key;
            else $tmpPath = $path . '_' . $key;

            if (is_array($value) && $content != NULL) $this->contentToFlatArray($value, $tmpPath, $schema);
            else {
                $schema[$tmpPath] = $value;
            }
        }
        return $schema;
    }
}

class Delete extends Action
{

    public function run()
    {
        $model = new $this->pars[0]();
        $model = $model::getByRequest();
        $uModel = $model->delete();
        $this->send($model->getModelName() . ' ' . $model->getId() . ', successfully deleted');
    }


    public function getActionInterface()
    {
        $model = new $this->pars[0]();
        $struct = [$model->getModelName() => $model->getDeleteActionStruct()];
        $flatStructArr = $this->contentToFlatArray($struct);
        $flatStruct = [];
        foreach ($flatStructArr as $k => $fieldStruct) {
            $flatStructArr[$k] = explode(Model::CHECK_SEP, $fieldStruct);
            $flatStruct[$k] = [];
            foreach ($flatStructArr[$k] as $check) {
                if ($check !== '') {
                    $flatStruct[$k][] = $check;
                }
            }
        }
        return [
            "name" => "STD_UPDATE",
            "struct" => $struct,
            "flat-struct" => $flatStruct
        ];
    }

    private function contentToFlatArray($content, &$path = '', &$schema = null)
    {
        if ($schema == null) $schema = array();
        foreach ($content as $key => $value) {
            if (strcmp($path, '') == 0) $tmpPath = $key;
            else $tmpPath = $path . '_' . $key;

            if (is_array($value) && $content != NULL) $this->contentToFlatArray($value, $tmpPath, $schema);
            else {
                $schema[$tmpPath] = $value;
            }
        }
        return $schema;
    }

    public function getResponseStruct(){
        $model = new $this->pars[0]();
        return 'message';
    }

    public function getRequestStruct(){
        $model = new $this->pars[0]();
        return [$model->getModelName()=>$model->getDeleteActionStruct()];
    }
}