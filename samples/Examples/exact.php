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
  "input1" => 1234567890,

  // Input 2
  "input2" => "uma string qualquer",

  // Input 3
  "input3" => 4564.56,

  // Input 4 - Not sent
  //"input4" => "",

  // Input 5
  "input5" => "invalid",
];

$validator = new Validator($form);

/*
 * exact: Confirm the number of characters
 * # Desired error code (Must be integer)
 * : Inform the desired amount
 */
$input1 = $validator->rules('input1', 'exact:10');
$input2 = $validator->rules('input2', 'exact:19');
$input3 = $validator->rules('input3', 'exact:7');
$input4 = $validator->rules('input4', 'exact:12');
$input5 = $validator->rules('input5', 'exact:12');

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
