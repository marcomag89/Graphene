<?php
include_once 'settings.php';

require '../Graphene.class.php';
use Graphene\Graphene;

$G = Graphene::getInstance($__GRAPHENE_SETTINGS);
$G->start();