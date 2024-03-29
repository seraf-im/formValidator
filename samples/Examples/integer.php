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

$form = [
  // Input 1
  "input1" => 26,

  // Input 2
  "input2" => 9223372036854775807,

  // Input 3
  "input3" => 0b11111111,

  // Input 4 - Not sent
  //"input4" => "",

  // Input 5
  "input5" => "26",
];

$validator = new Validator($form);

/*
 * integer: Confirm if it is whole numbers
 * # Desired error code (Must be integer)
 */
$input1 = $validator->rules('input1', 'integer');
$input2 = $validator->rules('input2', 'integer');
$input3 = $validator->rules('input3', 'integer');
$input4 = $validator->rules('input4', 'integer');
$input5 = $validator->rules('input5', 'integer');

// If there are errors, it returns json with the errors, if everything returns null
$errors = $validator->errors();

//See the result
echo "<pre>";
var_dump([
  "input1" => $input1,
  "input2" => $input2,
  "input3" => $input3,
  "input4" => $input4,
  "input5" => $input5,

  // Erro
  "result" => $errors
]);
echo "</pre>";
