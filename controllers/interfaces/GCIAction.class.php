<?php
namespace Graphene\controllers\interfaces;

/** Graphene collection interface action */
use Graphene\controllers\Action;
use Graphene\Graphene;
use Graphene\models\Model;
use Graphene\models\ModelCollection;


abstract class GCIAction extends Action {

    protected $queryParams;

    public function run() {
        if (array_key_exists('qParams', $this->request->getData())) {
            $this->queryParams = json_decode($this->request->getData()['qParams'], true);
        }
        $model = $this->getModelInstance();
        $data  = $this->getIncomingQuery();
        $pageSize = $this->getPageSize($data);
        $page     = $this->getPageNo($data);
        $gQuery   = $this->getStorageQuery($data);

        $readed = $this->fetch($model, $gQuery, $page, $pageSize);

        if ($readed instanceof ModelCollection) {

            $cursor = $this->getCursor($data, $readed);
            $results = [];
            foreach ($readed as $item) {
                $item->onSend();
                array_push($results, [$item->getModelName() => $this->formatItem($item->getContent())]);
            }
            $this->send([
                            'Collection' => $results,
                            'cursor'     => $cursor
                        ]);
        } else {
            $this->send(null);
        }
    }

    protected abstract function getModelInstance();

    protected function getIncomingQuery() {
        return $this->request->getData();
    }

    protected function getPageSize($data) {
        return (array_key_exists('page', $data) && array_key_exists('size', $data['page'])) ? intval($data['page']['size']) : null;
    }

    protected function getPageNo($data) {
        return (array_key_exists('page', $data) && array_key_exists('no', $data['page'])) ? intval($data['page']['no']) : 1;
    }

    protected function getStorageQuery($data) {
        return [
            'search' => $this->getSearchQuery($data),
            'sort'   => [
                'by'   => $this->getSortField($data),
                'mode' => $this->getSortMode($data)
            ]
        ];
    }

    protected function getSearchQuery($data) {
        return array_key_exists('search', $data) && $data['search'] !== '' ? $data['search'] : null;
    }

    protected function getSortField($data) {
        return (array_key_exists('sort', $data) && array_key_exists('by', $data['sort']) && ($data['sort']['by'] !== null)) ? $data['sort']['by'] : '';
    }

    protected function getSortMode($data) {
        return (array_key_exists('sort', $data) && array_key_exists('discend', $data['sort']) && boolval($data['sort']['discend'])) ? 'DSC' : 'ASC';
    }

    protected function fetch($model, $storageQuery, $page, $pageSize) {
        $model->setLazy(true);

        return $model->read(true, $storageQuery, $page, $pageSize);
    }

    protected function getCursor($data, $readed) {
        $page = $readed->getPage();
        $pageSize = $readed->getPageSize();
        $query = $this->getSearchQuery($data);
        $sortBy = $this->getSortField($data);
        $sortMode = $this->getSortMode($data);

        //$protocol = stripos($_SERVER['SERVER_PROTOCOL'], 'https') === true ? 'https://' : 'http://';
        $protocol = (strtolower($_SERVER['HTTPS']) == 'on') ? 'https://' : 'http://';

        $url = $protocol . $_SERVER['HTTP_HOST'] . Graphene::getInstance()->getSettings()['baseUrl'] . $this->request->getUrl();

        $httpQ = [
            'search'       => $query,
            'sort_by'      => $sortBy,
            'sort_discend' => (($sortMode === 'DSC') ? '1' : '0'),
            'page_size'    => $pageSize,
            'page_no'      => $page
        ];

        $httpQN = $httpQ;
        $httpQN['page_no'] = $httpQN['page_no'] + 1;

        $httpQP = $httpQ;
        $httpQP['page_no'] = $httpQP['page_no'] - 1;

        $cursor = [];
        $cursor['nxt'] = $url . '?' . http_build_query($httpQN);
        $cursor['cur'] = $url . '?' . http_build_query($httpQ);
        if ($page > 1) {
            $cursor['prv'] = ($url . '?' . http_build_query($httpQP));
        }

        return $cursor;
    }

    protected function formatItem($item) {
        return $item;
    }

    public function getRequestStruct() {
        return [
            'search' => Model::STRING,
            'sort'   => [
                'by'      => Model::STRING,
                'discend' => Model::BOOLEAN
            ],
            'page'   => [
                'size' => Model::INTEGER,
                'no'   => Model::INTEGER
            ]
        ];
    }


    /*
     * GRAPHENE DOC
     *
     *
     * */

    public function getResponseStruct() {
        $model = $this->getModelInstance();
        if ($model instanceof Model) {
            return [
                'Collection' => [[$this->getItemName() => $this->getItemStruct()]],
                'cursor'     => [
                    'nxt' => Model::STRING,
                    'cur' => Model::STRING,
                    'prv' => Model::STRING
                ]
            ];
        }
    }

    protected function getItemName() {
        return $this->getModelInstance()->getModelName();
    }

    protected function getItemStruct() {
        return $this->getModelInstance()->getReadActionStruct();
    }

    public function getActionInterface() {
        $struct = [$this->getItemName() => $this->getItemStruct()];
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
            "name"             => "GCI",
            "item-struct"      => $struct,
            "item-flat-struct" => $flatStruct
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

    public function getDescription() {
        $modName = $this->getItemName();

        return "# Read " . $modName . " collection\n this action allows to read collection of " . $modName . " instances, implementing **GCI** (*Graphene Collection Interface*).\nThis interface is automatically paged and you can quest this action using search and sort url parameters\n\n* **page_no** page selector, default is **1**\n* **page_size** allows to select a page size, default is **20**\n* **search** search string default is an empty string\n* **sort_by** set this parameter with name of field\n* **sort_discend** if value is '1' sort will bi discend default **0**\n\n" . parent::getDescription();
    }
}
