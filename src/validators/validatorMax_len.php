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

class validatorMax_len implements validatorInterface
{

    private $value;
    private $option;
    private $error = null;
    private $code = null;

    public function setValue($value): void
    {
        $this->value = (string) $value;
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
        if ((!\is_null($this->value)) && (\strlen($this->value) > $this->option)) {
            $this->error = "must contain a maximum {$this->option} characters";
            return "_false";
        }
        return ($this->value === null || $this->value === '') ? NULL : $this->value;
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
