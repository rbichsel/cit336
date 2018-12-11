<?php

/*
 * Accounts Controller
 */

session_start();

//Get the database connection file
require_once '../library/connections.php';
//Get the acme model
require_once '../model/acme-model.php';
//Get the accounts model
require_once '../model/accounts-model.php';
//Get the functions library
require_once '../library/functions.php';

$categories = getCategories();
//var_dump($categories);
//exit;

$action = filter_input(INPUT_POST, 'action');
if ($action == NULL) {
   $action = filter_input(INPUT_GET, 'action');
}

switch ($action) {
   case 'loginPage':
      include '../view/login.php';
      break;
   case 'registration':
      include '../view/registration.php';
      break;
   case 'register':
      //Filter and store the data
      $clientFirstname = filter_input(INPUT_POST, 'clientFirstname', FILTER_SANITIZE_STRING);
      $clientLastname = filter_input(INPUT_POST, 'clientLastname', FILTER_SANITIZE_STRING);
      $clientEmail = filter_input(INPUT_POST, 'clientEmail', FILTER_SANITIZE_EMAIL);
      $clientPassword = filter_input(INPUT_POST, 'clientPassword', FILTER_SANITIZE_STRING);

      $clientEmail = checkEmail($clientEmail);
      $checkPassword = checkPassword($clientPassword);


      //check for existing email
      $existingEmail = checkExistingEmail($clientEmail);

      //check existing email address in the table
      if ($existingEmail) {
         $message = '<p class="message">That email address already exists. Do you want to login instead?</p>';
         include '../view/login.php';
         exit;
      }

      //Check for missing data
      if (empty($clientFirstname) || empty($clientLastname) || empty($clientEmail) || empty($checkPassword)) {
         $message = '<p class="message">Please provide information for all empty form fields.</p>';
         include '../view/registration.php';
         exit;
      }

      //Hash the checked password
      $hashedPassword = password_hash($clientPassword, PASSWORD_DEFAULT);
      //send the data to the model
      $regOutcome = regClient($clientFirstname, $clientLastname, $clientEmail, $hashedPassword);

      // Check and report the result
      if ($regOutcome === 1) {
         setcookie('firstname', $clientFirstname, strtotime('+1 year'), '/');
         $message = "<p class='message'>Thanks for registering $clientFirstname. Please use your email and passward to login.</p>";
         $_SESSION['message'] = $message;
         header('Location: /acme/accounts/?action=loginPage');
         exit;
      } else {
         $message = "<p>Sorry $clientFirstname, but the registration failed. Please try again.</p>";
         include '../view/registration.php';
         exit;
      }
      break;

   case 'Login':

      $clientEmail = filter_input(INPUT_POST, 'clientEmail', FILTER_SANITIZE_EMAIL);
      $clientEmail = checkEmail($clientEmail);
      $clientPassword = filter_input(INPUT_POST, 'clientPassword', FILTER_SANITIZE_STRING);
      $passwordCheck = checkPassword($clientPassword);

// Run basic checks, return if errors
      if (empty($clientEmail) || empty($passwordCheck)) {
         $message = '<p class="message">Please provide a valid email address and password.</p>';
         include '../view/login.php';
         exit;
      }

// A valid password exists, proceed with the login process
// Query the client data based on the email address
      $clientData = getClient($clientEmail);
// Compare the password just submitted against
// the hashed password for the matching client

      $hashCheck = password_verify($clientPassword, $clientData['clientPassword']);
// If the hashes don't match create an error
// and return to the login view
      if (!$hashCheck) {
         $message = '<p class="message">Please check your password and try again.</p>';
         include '../view/login.php';
         exit;
      }
// A valid user exists, log them in
 $_SESSION['loggedin'] = TRUE;
 
//Delete firstname cookie
 
   setcookie('firstname', '$clientFirstname', time() - 4200, '/');

// Remove the password from the array
// the array_pop function removes the last
// element from an array
      array_pop($clientData);
// Store the array into the session
      $_SESSION['clientData'] = $clientData;
// Send them to the admin view
      include '../view/admin.php';
      exit;

// log user out
   case 'Logout':
      $_SESSION = array();
      if (ini_get("session.use_cookies")) {
         $params = session_get_cookie_params();
         setcookie(session_name(), '', time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]
         );
      }
      session_destroy();
      header('Location: /acme/');
      exit;

   case 'update':
      $clientId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
      $clientInfo = getClientInfo($clientId);
      //return $clientInfo;
      //exit;
      if (count($clientInfo) < 1) {
         $message = 'Sorry, no account information could be found.';
      }
      include '../view/client-update.php';
      exit;
      break;

   case 'account-update':
      $clientFirstname = filter_input(INPUT_POST, 'clientFirstname', FILTER_SANITIZE_STRING);
      $clientLastname = filter_input(INPUT_POST, 'clientLastname', FILTER_SANITIZE_STRING);
      $clientEmail = filter_input(INPUT_POST, 'clientEmail', FILTER_SANITIZE_EMAIL);
      $clientId = $_SESSION['clientData']['clientId'];

      $clientEmail = checkEmail($clientEmail);

      if (empty($clientEmail) || empty($clientLastname) || empty($clientFirstname)) {
         $messageAccount = '<p class="message">Please provide information for empty fields.</p>';
         include '../view/client-update.php';
         exit;
      }


      // Check for an existing email in the database
      $existingEmail = checkExistingEmail($clientEmail);
      // Check for match with current session email
      if ($clientEmail != $_SESSION['clientData']['clientEmail']) {
         if ($existingEmail) {
            $messageAccount = '<p class="message">That email is already in use. Please provide another email.</p>';
            include '../view/client-update.php';
            exit;
         }
      }

      $updateClient = updateClient($clientFirstname, $clientLastname, $clientEmail, $clientId);

      if ($updateClient === 1) {
         $_SESSION['message'] = "Congragulations! Your account information was updated!";
      } else {
         $_SESSION['message'] = "Sorry, you account was not updated. Did you make changes?";
      }

      $clientInfo = getClientInfo($clientId);

      $_SESSION['clientData'] = $clientInfo;

      include '../view/admin.php';
      exit;
      break;

   case 'change-pass':
      //Filter and store the data
      $clientPassword = filter_input(INPUT_POST, 'clientPassword', FILTER_SANITIZE_STRING);
      $clientId = $_SESSION['clientData']['clientId'];

      $checkPassword = checkPassword($clientPassword);

      //Check for missing data
      if (empty($checkPassword)) {
         $messagePass = '<p class="message">Please provide information for all empty form fields.</p>';
         include '../view/client-update.php';
         exit;
      }

      //Hash the checked password
      $hashedPassword = password_hash($clientPassword, PASSWORD_DEFAULT);
      //send the data to the model
      $newPassword = newPassword($hashedPassword, $clientId);

      // Check and report the result
      if ($newPassword === 1) {
         $_SESSION['message'] = "Your password as been changed.";
         header('Location: /acme/accounts/');
         exit;
      } else {
         $_SESSION['message'] = "Sorry, your password did not changed. Would you like to try again?";
         header('Location: /acme/accounts/');
      }
      break;

   default:
      include '../view/admin.php';
      break;
}