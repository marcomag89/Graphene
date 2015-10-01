<?php
namespace Graphene\db;

interface CrudDriver
{

    public function __construct($dbConfig);

    public function getConnection();

    public function getSettings();

    public function getInfos();

    public function create($json);

    public function read($json,$query=null);

    public function update($json);

    public function delete($json);

    const CONNECTION_ERROR_CODE = 100;

    const INITALIZATION_ERROR_CODE = 101;

    const PARSING_ERROR_CODE = 102;

    const QUERY_ERROR_CODE = 103;

    /* Errori di creazione */
    const CREATING_GENERIC_ERROR_CODE = 140;

    const CREATING_ID_EXISTS_ERROR_CODE = 141;

    /* Errori di lettura */
    const READING_GENERIC_ERROR_CODE = 150;

    /* Errori di scrittura */
    const DELETION_GENERIC_ERROR_CODE = 160;

    /* Errori di modifica */
    const EDITING_GENERIC_ERROR_CODE = 170;

    /* default messages */
    const CONNECTION_ERROR_MESSAGE = 'Connection error';

    const INITALIZATION_ERROR_MESSAGE = 'Initialization error';
}