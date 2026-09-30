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

        $chave = self::normalize((string) $this->value);

        if (strlen($chave) !== 44) {
            $this->error = "A Chave de Acesso deve conter exatamente 44 dígitos numéricos.";
            return "_false";
        }

        // NT Conjunta 2025.001 (CNPJ alfanumérico): letras só nas 12 primeiras
        // posições do CNPJ do emitente (posições 7–18 da chave); os 2 DVs do
        // CNPJ e todo o resto continuam numéricos.
        if (!preg_match('/^[0-9]{6}[A-Z0-9]{12}[0-9]{26}$/', $chave)
            || preg_match("/^{$chave[0]}{44}$/", $chave)
        ) {
            $this->error = "A Chave de Acesso informada é inválida.";
            return "_false";
        }

        if (!$this->validateDv($chave)) {
            $this->error = "O Dígito Verificador da Chave de Acesso é inválido.";
            return "_false";
        }

        // Chave com CNPJ alfanumérico: devolve em MAIÚSCULO (o formato oficial);
        // chave puramente numérica → valor original intacto (legado).
        if (is_string($this->value) && preg_match('/[A-Z]/', $chave)) {
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
     * Remove máscara/espaços (qualquer caractere fora de [A-Za-z0-9]), passa
     * para MAIÚSCULO e descarta um prefixo só de letras antes do 1º dígito —
     * o "Id" do XML vem como "NFe3519...", "CTe...", "MDFe..." e o legado
     * (que apagava toda não-dígito) aceitava essa forma.
     */
    public static function normalize(string $value): string
    {
        $chave = strtoupper((string) preg_replace('/[^A-Za-z0-9]/', '', $value));

        return (string) preg_replace('/^[A-Z]+(?=[0-9])/', '', $chave);
    }

    /**
     * cDV da chave: módulo 11 sobre as 43 primeiras posições, pesos 2..9 da
     * direita para a esquerda; resto 0 ou 1 → DV 0. Com CNPJ alfanumérico
     * (NT Conjunta 2025.001) cada caractere vale ASCII − 48 — para dígitos é
     * exatamente o valor numérico, então chaves antigas não mudam.
     */
    public static function computeDv(string $corpo43): int
    {
        $peso = 2;
        $soma = 0;

        for ($i = strlen($corpo43) - 1; $i >= 0; $i--) {
            $soma += (ord($corpo43[$i]) - 48) * $peso;

            $peso++;
            if ($peso > 9) {
                $peso = 2;
            }
        }

        $resto = $soma % 11;

        return ($resto == 0 || $resto == 1) ? 0 : (11 - $resto);
    }

    private function validateDv(string $chave): bool
    {
        return (int) $chave[43] === self::computeDv(substr($chave, 0, 43));
    }
}
