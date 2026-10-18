<?php

/**
 * #############################################################################
 * #############################################################################
 *
 * ################    ###          ###     ###          ###    ################
 * ################    ####         ###     ###          ###    ################
 * ################    #####        ###     ###          ###    ################
 * ###          ###    ######       ###     ###          ###    ###
 * ###          ###    ######       ###     ###          ###    ###
 * ###          ###    ### ###      ###     ###          ###    ###    .COM.BR
 * ###          ###    ###  ###     ###     ################    ################
 * ###          ###    ###   ###    ###     ################    ################
 * ###          ###    ###    ###   ###     ################    ################
 * ################    ###     ###  ###     ################    ################
 * ################    ###      ### ###     ###          ###                 ###
 * ################    ###       ######     ###          ###                 ###
 * ###                 ###        #####     ###          ###                 ###
 * ###                 ###         ####     ###          ###    ################
 * ###                 ###          ###     ###          ###    ################
 * ###                 ###           ##     ###          ###    ################
 *
 * #############################################################################
 *                         TODOS OS DIREITOS RESERVADOS!
 *                    O SENHOR E MEU PASTOR E NADA ME FALTARÁ
 * #############################################################################
 * #############################################################################
 *                            INICIO CODIGO DE FONTE!
 * #############################################################################
 * @package   PNHS Form-Validator
 * @author    PNHS <contato@pnhs.com.br>
 * @copyright 2023 49.022.455 NICOLA HENRIQUE SERAFIM
 * @license   Proprietary - All Rights Reserved
 * @link      http://www.pnhs.com.br
 */

declare(strict_types=1);

namespace Pnhs\FormValidator\validators;

use Pnhs\FormValidator\ValidatorInterface;
use Pnhs\FormValidator\validators\Phone\BRA;

class validatorPhone implements validatorInterface
{
    private $value;
    private $option;
    private $error = null;
    private $code = null;

    public function setValue($value): void
    {
        $this->value = $value;
    }

    public function setOption(string $option): void
    {
        $this->option = $option;
    }

    public function setCode(string $code): void
    {
        $this->code = $code;
    }

    public function execute()
    {
        if ($this->value === null || $this->value === '')
            return null;

        $options = explode(',', $this->option);

        $length = strlen($this->value);

        // País vazio/desconhecido (ex.: PATCH com `number` sem `country`) era
        // `new Phone\` → Error 500; agora é erro de validação.
        $c = 'Pnhs\FormValidator\validators\Phone\\' . $options[0];
        if ($options[0] === '' || !class_exists($c)) {
            $this->error = "country is not supported";
            return "_false";
        }

        $class = new $c();
        $r = $class->run($this->value, $length);

        if (!$r) {
            // Contrato do Validator: só a sentinela "_false" registra erro.
            // `false` aqui fazia o PATCH gravar '' e APAGAR o telefone (spec 487).
            $this->error = "is not valid";
            return "_false";
        }

        if (isset($options[1]) && $options[1] == "true")
            return array_merge(['number' => $this->value], $r);
        else
            return $this->value;
    }

    public function error()
    {
        return $this->error;
    }

    public function code()
    {
        return $this->code;
    }
}
