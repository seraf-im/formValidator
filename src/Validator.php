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

namespace Pnhs\FormValidator;

/**
 *
 * @author nicolahsss
 */
class Validator
{

    private $error;
    private $data;

    function __construct($data)
    {
        $this->data = $data;
    }

    public function rules(string $name, string $validators)
    {

        if (is_array($this->data)) {
            $data = ($this->data[$name] ?? null);
        } else {
            $data = ($this->data ?? null);
        }
        return $this->validators($name, $data, $validators);
    }

    private function validators($name, $data, string $validators)
    {
        $validators_explode = \explode('|', $validators);
        foreach ($validators_explode as $value) {
            $value_hash = \explode('#', $value);
            $value_option = \explode(':', $value_hash[0]);
            $validator = (string) $value_option[0];
            array_shift($value_option);
            $option = implode(':', $value_option);
            $code = ($value_hash[1] ?? '');

            $model = ValidatorFactory::build($validator);
            $model->setValue($data);
            $model->setOption($option);
            $model->setCode((int) $code);

            $result = $model->execute();

            if ($result === false) {
                $this->setError($name, $name . ' ' . $model->error(), $model->code(), $validator);
                return null;
            }
        }
        return $result;
    }

    public function errors(): ?array
    {
        return $this->error;
    }

    private function setError($name, $error, $code, $type): void
    {
        if (!is_array($this->error)) {
            $this->error = array();
        }

        array_push($this->error, [
            'parameter' => $name,
            'error' => $error,
            'code' => $code,
            'type' => $type
        ]);
    }
}
