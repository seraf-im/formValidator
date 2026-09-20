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

namespace Pnhs\FormValidator;

class Validator
{
    private $error;
    private $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function rules(string $name, string $validators = 'null')
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
            $model->setCode((string) $code);

            $result = $model->execute();

            // -----------------------------------------------------------------
            // CONTRATO DE REPROVAÇÃO: um validador reprova devolvendo a STRING
            // sentinela "_false". Só ela dispara setError() aqui.
            //
            // Por que uma sentinela em string, e não um simples teste falsy:
            // validadores devolvem o VALOR SANEADO no caminho de sucesso, e
            // vários desses valores são legitimamente falsy — validatorBoolean
            // devolve int 0, validatorMax devolve float 0.0, validatorNumeric e
            // validatorMin_len devolvem null, validatorString devolve ''. Um
            // `if (!$result)` reprovaria todos esses acertos. A sentinela
            // "_false" não colide com nenhum valor de payload real, por isso é
            // ela que marca falha.
            //
            // FAMÍLIA DE BUG (falha ABERTA silenciosa): validadores legados
            // reprovam devolvendo o BOOLEANO `false`. Como `false !== "_false"`,
            // o erro NUNCA era registrado — a regra não bloqueava nada e o valor
            // inválido seguia adiante como `false`, que o Model castava pra 0/''
            // sem alarme. Já normalizados: min_len, decimal, max.
            //
            // ⚠️ NÃO transformar este `===` em teste falsy/`|| $result === false`
            // sem antes auditar validador a validador. Medido em PHP 8.2 sobre o
            // código atual: validatorTimezone devolve `false` até para o fuso
            // VÁLIDO 'America/Sao_Paulo' (usa timezone_abbreviations_list(), que
            // não cobre esse id) e validatorInteger devolve `false` para a string
            // '5' (is_int() é falso para numérico vindo de JSON). Ligar o
            // comparador sem corrigir esses casos passa a rejeitar requisição
            // legítima em produção. A migração correta é normalizar cada
            // validador para "_false", um de cada vez, com o impacto medido.
            // -----------------------------------------------------------------
            if ($result === "_false") {
                $this->setError($name, $name . ' ' . $model->error(), $model->code(), $validator);
                return null;
            }

            // Telemetria da migração — NÃO altera o resultado da validação.
            // Marca no log os validadores que reprovaram com bool `false` e que,
            // por isso, NÃO bloquearam. Permite medir com tráfego real quais
            // regras de fato disparam antes de normalizá-las (blast radius).
            // Só o nome do campo e o da regra vão pro log — nenhum valor de
            // payload, para não vazar dado de cliente.
            if ($result === false) {
                \error_log(\sprintf(
                    '[form_validator] regra "%s" reprovou o campo "%s" mas devolveu bool false; '
                    . 'o contrato exige "_false" — falha ABERTA, o valor seguiu sem bloqueio',
                    $validator,
                    $name
                ));
            }
        }
        return $result;
    }

    public function errors(): ?array
    {
        return $this->error;
    }

    public function setError($name, $error, $code, $type): void
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
