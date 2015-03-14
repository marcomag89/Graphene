<?php
namespace Graphene\controllers\exceptions;

class ExceptionCodes
{
    // Framework error
    const FRAMEWORK = 100;
    
    // Module error
    const MODULE = 1000;
    
    // Action error
    const ACTION = 2000;
    
    // Storage driver exception
    const DRIVER = 3000;

    const DRIVER_CONNECTION = 3001;

    const DRIVER_INITIALIZATION = 3002;

    const DRIVER_PARSING = 3003;

    const DRIVER_QUERYNG = 3004;

    const DRIVER_CREATE = 3100;

    const DRIVER_CREATE_ID_EXISTS = 3101;

    const DRIVER_READ = 3200;

    const DRIVER_DELETE = 3300;

    const DRIVER_UPDATE = 3400;

    // Storage exception
    const STORAGE = 4000;

    const STORAGE_DOMAIN = 4001;

    const STORAGE_CREATE = 4100;

    const STORAGE_CREATE_ID_EXISTS = 4101;

    const STORAGE_READ = 4200;

    const STORAGE_READ_BEAN_CORRUPT = 4201;

    const STORAGE_DELETE = 4300;

    const STORAGE_DELETE_NOT_FOUND = 4301;

    const STORAGE_DELETE_VERSION = 4302;

    const STORAGE_UPDATE = 4400;

    const STORAGE_UPDATE_NOT_FOUND = 4401;

    const STORAGE_UPDATE_VERSION = 4402;

    const STORAGE_PATCH = 4500;

    const STORAGE_PATCH_NOT_FOUND = 4501;

    const STORAGE_PATCH_VERSION = 4502;
    
    // Models exception
    const BEAN = 5000;

    const BEAN_STRUCT = 5001;

    const BEAN_CONTENT_VALID = 5002;
    
    // Request
    const REQUEST = 6000;

    const REQUEST_MALFORMED = 6100;
}