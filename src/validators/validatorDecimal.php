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
 * @package   Validator
 * @author    PNHS <contato@pnhs.com.br>
 * @copyright 2023 49.022.455 NICOLA HENRIQUE SERAFIM
 * @license   Proprietary - All Rights Reserved
 * @link      http://www.pnhs.com.br
 */

declare(strict_types=1);

namespace Pnhs\FormValidator\validators;

use Pnhs\FormValidator\ValidatorInterface;

class validatorDecimal implements validatorInterface
{
    private $value;
    private $option = "2";
    private $valueOption;
    private $error = null;
    private $code = null;

    public function setValue($value): void
    {
        $this->value = $value;
    }

    public function setOption(string $option): void
    {
        if ($option) {
            $this->valueOption = $option;
            $this->option = $option;
        }
    }

    public function setCode(string $code): void
    {
        $this->code = $code;
    }

    public function execute()
    {
        if (is_null($this->value) || $this->value === "") {
            return null;
        }

        if (!is_numeric($this->value)) {
            $this->error = "is not a valid number";
            return "_false";
        }

        $valStr = (string) $this->value;

        $options = explode(',', (string) $this->option);
        $maxDecimals = (int) ($options[0] ?? 2);
        $maxIntegerDigits = isset($options[1]) ? (int) $options[1] : null;

        if (strpos($valStr, '.') !== false) {
            list($integerPart, $decimalPart) = explode('.', $valStr);
        } else {
            $integerPart = $valStr;
            $decimalPart = "";
        }

        if (strpos($integerPart, '-') !== false) {
            $integerPart = str_replace('-', '', $integerPart);
        }

        if (!is_null($maxIntegerDigits)) {
            $cleanInt = ltrim($integerPart, '0');
            if ($cleanInt === '') $cleanInt = '0';

            if (strlen($cleanInt) > $maxIntegerDigits) {
                $this->error = "cannot have more than {$maxIntegerDigits} digits before the decimal point";
                return "_false";
            }
        }

        if (strlen($decimalPart) > $maxDecimals) {
            $this->error = "cannot have more than {$maxDecimals} decimal places";
            return "_false";
        }

        return number_format((float) $this->value, $maxDecimals, '.', '');
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
