<?php
namespace Graphene\controllers\interfaces;


use Graphene\controllers\Action;
use Graphene\controllers\exceptions\GraphException;
use Graphene\models\Model;

abstract class StdRead extends Action {
    public function run() {
        $model = $this->getModelInstance();
        $id = $this->getRequestedId();
        $model->setId($id);
        $readed = $this->readModel($model);
        if ($readed != null) {
            $this->send($this->formatReadedModel($readed));
        } else {
            throw new GraphException("model not found", 404);
        }
    }

    protected abstract function getModelInstance();

    protected function getRequestedId() {
        $id = $this->request->getPar('id');
        if ($id === null || $id === '') {
            throw new GraphException('Invalid id', 400);
        }

        return $id;
    }

    protected function readModel(Model $model) {
        return $model->read();
    }

    /**
     * Allows action user to format model before send
     *
     * @param Model $model
     *
     * @return array | Model
     */
    protected function formatReadedModel($model) {
        return $model;
    }

    /**
     * DOC
     */

    public function getResponseStruct() {
        $model = $this->getModelInstance();

        return [$model->getModelName() => $model->getReadActionStruct()];
    }

    public function getActionInterface() {
        $model = $this->getModelInstance();
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
            "name"        => "STD_READ",
            "struct"      => $struct,
            "flat-struct" => $flatStruct
        ];
    }

    private function contentToFlatArray($content, &$path = '', &$schema = null) {
        if ($schema == null) {
            $schema = [];
        }
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

        return $schema;
    }
}