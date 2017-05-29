<?php
/**
 * Graphene index file
 */

include_once __DIR__ . '/vendor/autoload.php';
include_once 'settings.php';

use Graphene\Graphene;

$G = Graphene::getInstance($__GRAPHENE_SETTINGS);
$G->start();
