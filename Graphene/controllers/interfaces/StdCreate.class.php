<?php
namespace Graphene\controllers\interfaces;


use Graphene\controllers\Action;
use Graphene\models\Model;

abstract class StdCreate extends Action {
    public function run() {
        $model = $this->getModelFromRequest();
        $sModel = $this->createModel($model);
        $this->send($this->formatCreatedModel($sModel));
    }

    protected function getModelFromRequest() {
        $model = $this->getModelInstance();
        return $model::getByRequest();
    }

    protected abstract function getModelInstance();

    protected function createModel(Model $model) {
        return $model->create();
    }

    protected function formatCreatedModel(Model $createdModel) {
        return $createdModel;
    }

    /**
     * DOC
     */

    public function getResponseStruct() {
        $model = $this->getModelInstance();
        return [$model->getModelName() => $model->getReadActionStruct()];
    }

    public function getRequestStruct() {
        $model = $this->getModelInstance();
        return [$model->getModelName() => $model->getCreateActionStruct()];
    }

    public function getActionInterface() {
        $model = $this->getModelInstance();
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
            "name"        => "STD_CREATE",
            "struct"      => $struct,
            "flat-struct" => $flatStruct
        ];
    }

    private function contentToFlatArray($content, &$path = '', &$schema = null) {
        if ($schema == null) $schema = [];
        foreach ($content as $key => $value) {
            if (strcmp($path, '') == 0) $tmpPath = $key;
            else $tmpPath = $path . '_' . $key;

            if (is_array($value) && $content != null) $this->contentToFlatArray($value, $tmpPath, $schema);
            else {
                $schema[$tmpPath] = $value;
            }
        }
        return $schema;
    }
}