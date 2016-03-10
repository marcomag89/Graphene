<?php
namespace Graphene\db\drivers;


class MySqlQuery2 {
    private $queryComponents;

    public function __construct() {
        return $this;
    }

    public function action() {

    }

    public function schema() {
        return $this;

    }

    public function table() {
        return $this;

    }

    public function pageNo() {
        return $this;

    }

    public function pageSize() {

    }

    public function condition() {
        return $this;

    }

    public function filter() {
        return $this;

    }

    public function values() {
        return $this;

    }

    public function read(Model $model) {
        //crea la query base di lettura
        $this->queryComponents = [
            "action"    => "SELECT",
            "schema"    => "",
            "table"     => "",
            "pageNo"    => "",
            "pageSize"  => "",
            "condition" => "",
            "filter"    => ""
        ];
        return $this;
    }

    public function create(Model $model) {
        //crea la query base di creazione
        $this->queryComponents = [
            "action" => "INSERT",
            "schema" => "",
            "table"  => "",
            "values" => []
        ];
        return $this;

    }

    public function update(Model $model) {
        //crea la query base di modifica
        //UPDATE <identifier> SET <kv>  WHERE `id`=<id>';
        $this->queryComponents = [
            "action"    => "UPDATE",
            "schema"    => "",
            "table"     => "",
            "condition" => "",
            "values"    => []
        ];
        return $this;

    }

    public function delete(Model $model) {
        //crea la query base di cancellazione
        $this->queryComponents = [
            "action"    => "DELETE",
            "schema"    => "",
            "table"     => "",
            "condition" => ""
        ];
        return $this;
    }

    public function getComponents() {
        return $this->queryComponents;
    }

    public function toString() {
        return $this->queryComponents;
    }

    public function serialize() {
        return json_encode($this->queryComponents, JSON_PRETTY_PRINT);
    }

}