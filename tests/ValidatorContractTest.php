<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;
use Pnhs\FormValidator\Validator;

/**
 * Trava de contrato da família "falha ABERTA".
 *
 * Validator::validators() só registra erro quando o validador devolve a STRING
 * "_false". Os validadores abaixo ainda reprovam devolvendo o BOOLEANO `false`
 * e portanto NÃO bloqueiam nada hoje em produção.
 *
 * Este teste NÃO aprova esse comportamento — ele o CONGELA. Ao normalizar
 * qualquer um desses validadores para "_false" (ou ao ligar o comparador
 * contra `false`), o caso correspondente aqui quebra, forçando quem mexer a
 * medir o impacto nos call sites antes de subir. É produção fiscal: nenhuma
 * regra pode passar a bloquear por acidente.
 *
 * Já normalizados e fora desta lista: required, noEmpty, enum, exact, decimal,
 * min_len, max_len, document, gtin, accessKeyInvoiceBR, timestamp, max.
 */
class ValidatorContractTest extends TestCase
{
    /**
     * @dataProvider validadoresQueAindaNaoBloqueiam
     */
    public function testAindaNaoBloqueiaPorqueDevolveBoolFalse(string $rule, $valorInvalido): void
    {
        $validator = new Validator(['campo' => $valorInvalido]);
        $result = $validator->rules('campo', $rule);

        $this->assertFalse($result, "regra '{$rule}' deixou de devolver bool false");
        $this->assertNull(
            $validator->errors(),
            "regra '{$rule}' passou a BLOQUEAR — audite os call sites antes de liberar"
        );
    }

    public function validadoresQueAindaNaoBloqueiam(): array
    {
        return [
            // 167 call sites: um número vindo de JSON reprova aqui.
            'string com int'        => ['string', 8575],
            // 5 call sites: is_int('5') é falso — numérico de JSON reprova.
            'integer com numerico'  => ['integer', '5'],
            '  62 call sites'       => ['numeric', 'abc'],
            '  13 call sites'       => ['email', 'zzz'],
            '  97 call sites'       => ['array', 'x'],
            // date aceita null, mas reprova string vazia.
            'date com vazio'        => ['date:Y-m-d', ''],
            '   3 call sites'       => ['phone:BR', '1'],
        ];
    }

    /**
     * Landmine isolada: validatorTimezone usa timezone_abbreviations_list(),
     * que NÃO contém 'America/Sao_Paulo' (só 375 ids). Ou seja, ele reprova o
     * fuso mais usado do ERP. Enquanto devolver bool `false` isso é inócuo
     * (não bloqueia); no dia em que passar a bloquear, derruba requisição
     * legítima. Sem call site em regra hoje — o fuso da empresa é validado por
     * `enum:` em services/company. Corrigir para DateTimeZone::listIdentifiers()
     * ANTES de normalizar este validador.
     */
    public function testTimezoneReprovaFusoValidoMasNaoBloqueia(): void
    {
        $validator = new Validator(['tz' => 'America/Sao_Paulo']);

        $this->assertFalse($validator->rules('tz', 'timezone'));
        $this->assertNull($validator->errors());
    }

    /**
     * validatorPassword tem `return $this->value;` antes do match de força —
     * todo o regex de complexidade é código MORTO. Nenhuma senha é checada.
     */
    public function testPasswordNaoValidaForca(): void
    {
        $validator = new Validator(['senha' => 'abc']);

        $this->assertSame('abc', $validator->rules('senha', 'password:3'));
        $this->assertNull($validator->errors());
    }
}
