<?php
//Controllers imports
require_once __DIR__.DIRECTORY_SEPARATOR.'controllers/ConfigManager.php';
require_once __DIR__.DIRECTORY_SEPARATOR.'controllers/ConnectionManager.php';
require_once __DIR__.DIRECTORY_SEPARATOR.'controllers/CoreManager.php';

//Models import
require_once __DIR__.DIRECTORY_SEPARATOR.'models/StorageRequest.php';
require_once __DIR__.DIRECTORY_SEPARATOR.'models/RequestModel.php';

//Utilities imports
require_once __DIR__.DIRECTORY_SEPARATOR.'utils/ExceptionConverter.php';
require_once __DIR__.DIRECTORY_SEPARATOR.'utils/MySqlQuery.php';
require_once __DIR__.DIRECTORY_SEPARATOR.'utils/MySqlTypes.php';

//Interface implementation
require_once __DIR__.DIRECTORY_SEPARATOR.'masql.php';