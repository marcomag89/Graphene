<?php
namespace Graphene\controllers;

class ExceptionsCodes
{

	const CRUD_DRIVER_GENERIC = 1;

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

	const BEAN_STORAGE_GENERIC = 2;

	const BEAN_STORAGE_NULL_BEAN = 200;

	const BEAN_STORAGE_CORRUPTED_BEAN = 201;

	const BEAN_STORAGE_VERSION_MISMATCH = 202;

	const BEAN_STORAGE_NOT_FOUND_FOR_UPDATE = 203;

	const BEAN_STORAGE_NOT_FOUND_FOR_PATCH = 204;

	const BEAN_STORAGE_INVALID_DOMAIN = 205;

	const BEAN_STORAGE_VERSION_UNAVAILABLE = 206;

	const BEAN_STORAGE_ID_UNAVAILABLE = 206;

	const BEAN_STORAGE_ID_NOT_FOUND = 207;

	const REQUEST_GENERIC = 300;

	const REQUEST_MALFORMED = 301;

	const BEAN_GENERIC = 3;

	const BEAN_CORRUPTED = 300;
}