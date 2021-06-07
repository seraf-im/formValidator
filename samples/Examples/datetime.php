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
  // Input 1 - Sending date in year month day format
  "input1" => "2021-06-06 19:44:25",

  // Input 2 - Sending date in day month year format
  "input2" => "06-06-2021 19-44-25",

  // Input 3
  "input3" => "06/06/2021 19:44:25",

  // Input 4 - Not sent
  //"input4" => "",

  // Input 5 - Invalid
  "input5" => "2021-02-31 25:60:25",
];

$validator = new Validator($form);

/*
 * datetime: Must be a datetime if sent
 * string: Must be a string if sent
 * # Desired error code (Must be integer)
 * : Send if date format other than Y-m-d H:i:s
 */
$input1 = $validator->rules('input1', 'string|datetime');
$input2 = $validator->rules('input2', 'string|datetime:d-m-Y H-i-s');
$input3 = $validator->rules('input3', 'string|datetime:d/m/Y H:i:s#124');
$input4 = $validator->rules('input4', 'string|datetime#123');
$input5 = $validator->rules('input5', 'string|datetime');

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
