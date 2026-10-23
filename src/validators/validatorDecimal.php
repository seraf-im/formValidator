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

        $options = explode(',', (string) $this->option);
        $maxDecimals = (int) ($options[0] ?? 2);
        $maxIntegerDigits = isset($options[1]) ? (int) $options[1] : null;

        $valStr = $this->canonical($this->value, $maxDecimals);

        // Spec 498 — notação exponencial ("1e15", "1.0E+15") passava no
        // is_numeric e driblava o teto de inteiros. Só dígitos com ponto.
        if (preg_match('/^([+-]?)(\d*)(?:\.(\d*))?$/', $valStr, $m) !== 1 || ($m[2] === '' && ($m[3] ?? '') === '')) {
            $this->error = "is not a valid number";
            return "_false";
        }

        $negative = $m[1] === '-';
        $integerPart = $m[2];
        $decimalPart = $m[3] ?? '';

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

        // Spec 498 — saída montada por string. O antigo
        // number_format((float) ...) corrompia valores com mais de ~15
        // dígitos significativos (vUnCom 11v10: 12345678901.1234567891 →
        // ...4569550). Para entradas válidas o formato é o mesmo de antes.
        $int = ltrim($integerPart, '0');
        if ($int === '') $int = '0';
        $dec = str_pad($decimalPart, $maxDecimals, '0');
        $isZero = trim($int . $dec, '0') === '';

        return ($negative && !$isZero ? '-' : '') . $int . ($maxDecimals > 0 ? '.' . $dec : '');
    }

    /**
     * Texto do número sem perda: float vem do json_decode e o (string) com
     * precision=14 arredonda (99999999999.9999 → "100000000000"). O
     * json_encode usa a menor representação exata (serialize_precision=-1).
     * Se essa forma tiver mais casas que o permitido (ruído de ponto
     * flutuante, ex.: 0.1+0.2), cai na forma antiga, que era aceita.
     */
    private function canonical(mixed $value, int $maxDecimals): string
    {
        if (!is_float($value)) {
            return trim((string) $value);
        }

        $exact = (string) json_encode($value);
        $dot = strpos($exact, '.');
        if (stripos($exact, 'e') === false && ($dot === false || strlen($exact) - $dot - 1 <= $maxDecimals)) {
            return $exact;
        }

        return (string) $value;
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
