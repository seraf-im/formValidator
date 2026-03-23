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

class ValidatorGtin implements ValidatorInterface
{
    private $value;
    private $option = 'ALL';
    private $error = null;
    private $code = null;

    public function setValue(null|string|int $value): void
    {
        $this->value = $value;
    }

    public function setOption(string $option): void
    {
        $cleanOption = strtoupper(str_replace(' ', '', $option));
        $this->option = empty($cleanOption) ? 'ALL' : $cleanOption;
    }

    public function setCode(string $code): void
    {
        $this->code = $code;
    }

    public function execute()
    {
        if ($this->value === null || $this->value === '') {
            return $this->value;
        }

        $gtin = preg_replace('/\D/', '', (string) $this->value);
        $len = strlen($gtin);

        $validLengths = [
            8 => 'GTIN8',
            12 => 'GTIN12', // UPC
            13 => 'GTIN13', // EAN-13
            14 => 'GTIN14'  // DUN-14
        ];

        if (!array_key_exists($len, $validLengths)) {
            $this->error = "O código deve conter 8, 12, 13 ou 14 dígitos.";
            return "_false";
        }

        $detectedType = $validLengths[$len];

        if ($this->option !== 'ALL') {
            $allowedTypes = explode(',', $this->option);

            if (!in_array($detectedType, $allowedTypes)) {
                $this->error = "O formato identificado ($detectedType) não é permitido. Aceitos: {$this->option}.";
                return "_false";
            }
        }

        if (!$this->validateChecksum($gtin)) {
            $this->error = "Dígito verificador do GTIN inválido.";
            return "_false";
        }

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

    private function validateChecksum(string $gtin): bool
    {
        $originalCheckDigit = (int) substr($gtin, -1);
        $core = substr($gtin, 0, -1);
        $sum = 0;
        $multiplier = 3;

        for ($i = strlen($core) - 1; $i >= 0; $i--) {
            $sum += (int) $core[$i] * $multiplier;
            $multiplier = ($multiplier === 3) ? 1 : 3;
        }

        $nextTen = ceil($sum / 10) * 10;
        $calculatedCheckDigit = $nextTen - $sum;

        $calculatedCheckDigit = (10 - ($sum % 10)) % 10;

        return $calculatedCheckDigit === $originalCheckDigit;
    }
}
