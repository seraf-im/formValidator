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

class validatorAccessKeyInvoiceBR implements ValidatorInterface
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
        if ($this->value === null || $this->value === '') {
            return $this->value;
        }

        $valueString = (string) $this->value;
        $chave = preg_replace('/\D/', '', $valueString);

        if (strlen($chave) !== 44) {
            $this->error = "A Chave de Acesso deve conter exatamente 44 dígitos numéricos.";
            return "_false";
        }

        if (preg_match("/^{$chave[0]}{44}$/", $chave)) {
            $this->error = "A Chave de Acesso informada é inválida.";
            return "_false";
        }

        if (!$this->validateDv($chave)) {
            $this->error = "O Dígito Verificador da Chave de Acesso é inválido.";
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

    private function validateDv(string $chave): bool
    {
        $dvInformado = (int) $chave[43];
        $corpo = substr($chave, 0, 43);
        $peso = 2;
        $soma = 0;

        for ($i = 42; $i >= 0; $i--) {
            $digito = (int) $corpo[$i];
            $soma += $digito * $peso;

            $peso++;
            if ($peso > 9) {
                $peso = 2;
            }
        }

        $resto = $soma % 11;

        $dvCalculado = ($resto == 0 || $resto == 1) ? 0 : (11 - $resto);

        return $dvInformado === $dvCalculado;
    }
}
