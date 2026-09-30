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

class ValidatorDocument implements ValidatorInterface
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
        $this->option = strtoupper($option);
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

        $isValid = false;
        $label = '';
        $c = '';

        switch ($this->option) {
            case 'CPF':
                // CPF continua exclusivamente numérico: comportamento legado
                // (descarta tudo que não for dígito) mantido de propósito.
                $c = preg_replace('/\D/', '', $valueString);
                $isValid = $this->validateCpf($c);
                $label = 'CPF';
                break;
            case 'CNPJ':
                $c = self::normalizeAlphanumeric($valueString);
                $isValid = $this->validateCnpj($c);
                $label = 'CNPJ';
                break;
            case 'CPF_CNPJ':
                $c = self::normalizeAlphanumeric($valueString);
                if (strlen($c) === 11) {
                    $isValid = $this->validateCpf($c);
                } elseif (strlen($c) === 14) {
                    $isValid = $this->validateCnpj($c);
                }
                $label = 'CPF ou CNPJ';
                break;
            default:
                $this->error = "Invalid validation option: {$this->option}";
                return "_false";
        }

        if (!$isValid) {
            $this->error = "O valor informado não é um $label válido.";
            return "_false";
        }

        // CNPJ alfanumérico (IN RFB 2.229/2024): letras são aceitas em
        // minúsculo na entrada, mas o documento oficial é sempre MAIÚSCULO.
        // Devolve o valor original (máscara preservada) em caixa alta para que
        // o consumidor não persista "12abc..." e divirja do que vai à SEFAZ.
        // Documento puramente numérico → valor devolvido intacto (sem mudança
        // de comportamento para o legado).
        if (is_string($this->value) && preg_match('/[A-Z]/', $c)) {
            return strtoupper($this->value);
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

    /**
     * Normaliza CNPJ (numérico ou alfanumérico): remove máscara/pontuação/espaços
     * (qualquer caractere fora de [A-Za-z0-9], como o legado já fazia com os
     * símbolos) e passa letras para MAIÚSCULO.
     */
    public static function normalizeAlphanumeric(string $value): string
    {
        return strtoupper((string) preg_replace('/[^A-Za-z0-9]/', '', $value));
    }

    /**
     * Valor de cálculo de um caractere para DV (IN RFB 2.229/2024 e NT Conjunta
     * 2025.001): código ASCII − 48. Dígitos ficam 0..9 (idêntico ao cálculo
     * clássico); letras A..Z valem 17..42.
     */
    public static function charValue(string $char): int
    {
        return ord($char) - 48;
    }

    /**
     * DV módulo 11 com pesos 2..9 aplicados da DIREITA para a esquerda (ciclo
     * 2..9). Para a base de 12 posições do CNPJ gera 5,4,3,2,9..2 (DV1); para
     * 13 posições gera 6,5,4,3,2,9..2 (DV2) — o algoritmo clássico do CNPJ.
     * Resto < 2 → DV 0; senão 11 − resto.
     */
    public static function mod11Dv(string $base): int
    {
        $sum = 0;
        $weight = 2;
        for ($i = strlen($base) - 1; $i >= 0; $i--) {
            $sum += self::charValue($base[$i]) * $weight;
            $weight = ($weight === 9) ? 2 : $weight + 1;
        }
        $rest = $sum % 11;

        return ($rest < 2) ? 0 : 11 - $rest;
    }

    /**
     * Calcula os 2 DVs de uma base de CNPJ (12 posições [A-Z0-9], já
     * normalizada). Útil para testes e geração de vetores.
     */
    public static function cnpjCheckDigits(string $base12): string
    {
        $dv1 = self::mod11Dv($base12);
        $dv2 = self::mod11Dv($base12 . $dv1);

        return $dv1 . $dv2;
    }

    private function validateCpf(string $cpf): bool
    {
        if (strlen($cpf) != 11 || !ctype_digit($cpf) || preg_match("/^{$cpf[0]}{11}$/", $cpf)) {
            return false;
        }

        for ($t = 9; $t < 11; $t++) {
            for ($d = 0, $c = 0; $c < $t; $c++) {
                $d += $cpf[$c] * (($t + 1) - $c);
            }
            $d = ((10 * $d) % 11) % 10;
            if ($cpf[$c] != $d) {
                return false;
            }
        }
        return true;
    }

    /**
     * CNPJ numérico OU alfanumérico (IN RFB 2.229/2024): 12 posições [A-Z0-9]
     * (raiz + ordem) + 2 DVs sempre NUMÉRICOS. Espera a entrada já normalizada
     * por normalizeAlphanumeric().
     */
    private function validateCnpj(string $cnpj): bool
    {
        if (!preg_match('/^[A-Z0-9]{12}[0-9]{2}$/', $cnpj)) {
            return false;
        }

        // Sequência de um único caractere (00000000000000, 11111111111111...)
        // continua rejeitada, como no legado.
        if (preg_match('/^(.)\1{13}$/', $cnpj)) {
            return false;
        }

        return substr($cnpj, 12, 2) === self::cnpjCheckDigits(substr($cnpj, 0, 12));
    }
}
