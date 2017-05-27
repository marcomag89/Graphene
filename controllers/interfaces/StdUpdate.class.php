<?php
namespace Graphene\controllers\interfaces;

use Graphene\models\Model;
use Graphene\controllers\Action;

abstract class StdUpdate extends Action {

    /**
     * Graphene Run
     */
    public function run() {
        $model = $this->getModelFromRequest();
        $uModel = $this->updateModel($model);
        $this->send($this->formatUpdatedModel($uModel));
    }

    /**
     * Gets model content from request data
     * @return Model
     */
    protected function getModelFromRequest() {
        $model = $this->getModelInstance();
        return $model::getByRequest();
    }

    /**
     * Gets empty model instance from child class
     * @return Model
     */
    protected abstract function getModelInstance();

    /**
     * @param Model $model
     * @return Model
     */
    protected function updateModel($model) {
        return $model->update();
    }

    /**
     * Format model before send
     *
     * @param Model $model
     * @return Model
     */
    protected function formatUpdatedModel($model) {
        return $model;
    }

    public function getResponseStruct() {
        $model = $this->getModelInstance();
        return [$model->getModelName() => $model->getReadActionStruct()];
    }
    public function getRequestStruct() {
        $model = $this->getModelInstance();
        return [$model->getModelName() => $model->getUpdateActionStruct()];
    }
    public function getActionInterface() {
        $model = $this->getModelInstance();
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
            "name"        => "STD_UPDATE",
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