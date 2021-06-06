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

use Serafim\FormValidator\Validator;

require '../../vendor/autoload.php';

$form = [
  // Input 1
  "input1" => "1234567890",

  // Input 2
  "input2" => "uma string qualquer",

  // Input 3
  "input3" => "4564.56",

  // Input 4 - Not sent
  //"input4" => "",

  // Input 5
  "input5" => 8575,
];

$validator = new Validator($form);

/*
 * string: Confirm it is a string
 * # Desired error code (Must be integer)
 */
$input1 = $validator->rules('input1', 'string');
$input2 = $validator->rules('input2', 'string');
$input3 = $validator->rules('input3', 'string');
$input4 = $validator->rules('input4', 'string');
$input5 = $validator->rules('input5', 'string');

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
