<?php

include('functions.php');
include('mongodbHelper.php');
$configs = include 'config.php';

$content = file_get_contents("php://input");
$update = json_decode($content, true);
$mongodbHelper = new mongodbHelper();

if (!$update) {
  // receive wrong update, must not happen
  exit;
}

if (isset($update["message"])) {
  processMessage($update["message"], $mongodbHelper);
}

?>