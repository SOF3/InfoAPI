<?php

use SOFe\InfoAPI\Doc;
use SOFe\InfoAPI\Indices;
use SOFe\InfoAPI\MockInitContext;
use SOFe\InfoAPI\Registries;

require_once __DIR__ . "/vendor/autoload.php";

$indices = Indices::withDefaults(new MockInitContext, Registries::empty());
$doc = Doc::export($indices->registries, ...$indices->fallbackRegistries);
echo json_encode($doc, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
