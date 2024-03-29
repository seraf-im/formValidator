<?php

declare(strict_types=1);

###############################################################################################################
###############################################################################################################
##                                                                                                           ##
##  ######################     #####            #####     #####            #####     ######################  ##
##  ######################     #####            #####     #####            #####     ######################  ##
##  ######################     ######           #####     #####            #####     ######################  ##
##  ######################     #######          #####     #####            #####     ######################  ##
##  ######################     ########         #####     #####            #####     ######################  ##
##  #####            #####     #########        #####     #####            #####     #####                   ##
##  #####            #####     ##########       #####     #####            #####     #####     .COM.BR       ##
##  #####            #####     ##### #####      #####     ######################     ######################  ##
##  #####            #####     #####  #####     #####     ######################     ######################  ##
##  ######################     #####   #####    #####     ######################     ######################  ##
##  ######################     #####    #####   #####     ######################     ######################  ##
##  ######################     #####     #####  #####     ######################     ######################  ##
##  ######################     #####      ##### #####     #####            #####                      #####  ##
##  ######################     #####       ##########     #####            #####                      #####  ##
##  #####                      #####        #########     #####            #####     ######################  ##
##  #####                      #####         ########     #####            #####     ######################  ##
##  #####                      #####          #######     #####            #####     ######################  ##
##  #####                      #####           ######     #####            #####     ######################  ##
##  #####                      #####            #####     #####            #####     ######################  ##
##                                                                                                           ##
###############################################################################################################
##                   TODOS OS DIREITOS RESERVADOS  O SENHOR E MEU PASTOR E NADA ME FALTARÁ                   ##
###############################################################################################################
###############################################################################################################
###############################################################################################################
##                                          INICIO CÓDIGO DE FONTE!                                          ##
###############################################################################################################

use Pnhs\FormValidator\Validator;

require '../../vendor/autoload.php';

// Converting JSON to Array
$contents = json_decode(file_get_contents('php://input'), true);

$validator = new Validator($contents);

/*
 * required: Make mandatory
 * no_empty: Can not be empty
 * min_len: Minimum number of characters
 * max_len: Maximum number of characters
 */
$username = $validator->rules('username', 'required|no_empty|min_len:3|max_len:10');
$password = $validator->rules('password', 'required|no_empty|min_len:8');

// If there are errors, it returns json with the errors, if everything returns null
$error = $validator->errors();
$errors = [];

foreach ($error as $item) {
  $errors[$item['parameter']] = $item['error'];
}

if (is_null($error))
  die(json_encode([
    'status' => 'ok'
  ]));

die(json_encode([
  'status' => 'error',
  'errors' => $errors
]));
