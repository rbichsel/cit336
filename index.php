<?php

//Create or access a Session
session_start();

require_once 'library/connections.php';
require_once 'model/acme-model.php';
require_once 'model/products-model.php';
//Get the functions library
require_once 'library/functions.php';

$categories = getCategories();
//var_dump($categories);
//exit;

$action = filter_input(INPUT_POST, 'action');
if ($action == NULL) {
   $action = filter_input(INPUT_GET, 'action');
}

// Check if the firstname cookie exists, get its value
if (isset($_COOKIE['firstname'])) {
   $cookieFirstname = filter_input(INPUT_COOKIE, 'firstname', FILTER_SANITIZE_STRING);
}

switch ($action) {

   default:
     $newFeaturedItem = newFeaturedData();
     $featureDisplay =  featureDisplay($newFeaturedItem);
      include 'view/home.php';
      break;
}