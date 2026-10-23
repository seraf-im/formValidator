<?php

declare(strict_types=1);

namespace Tests\Validators;

use PHPUnit\Framework\TestCase;
use Pnhs\FormValidator\Validator;

/**
 * Regressão: validatorDecimal retornava `false` (bool) nos ramos de falha, mas
 * o Validator compara `=== "_false"` (string) — o erro nunca era registrado e o
 * `false` seguia como valor "válido" até o INSERT (`''` em coluna DECIMAL →
 * 1366, HTTP 500). Incidente 2026-09-08: duplicata de NF-e com vDup "1837,40"
 * (vírgula) derrubava o POST /manage do invoice_br_nfe. Mesmo padrão do fix do
 * min_len (cdd4d27).
 */
class validatorDecimalTest extends TestCase
{
    public function testRejeitaVirgulaComoSeparadorDecimal(): void
    {
        $validator = new Validator(['vDup' => '1837,40']);
        $result = $validator->rules('vDup', 'decimal:2');

        $this->assertNull($result);
        $errors = $validator->errors();
        $this->assertIsArray($errors);
        $this->assertSame('vDup', $errors[0]['parameter']);
        $this->assertSame('decimal', $errors[0]['type']);
        $this->assertStringContainsString('not a valid number', $errors[0]['error']);
    }

    public function testRejeitaTextoNaoNumerico(): void
    {
        $validator = new Validator(['vDup' => 'abc']);

        $this->assertNull($validator->rules('vDup', 'decimal:2'));
        $this->assertIsArray($validator->errors());
    }

    public function testAceitaStringComPontoENormaliza(): void
    {
        $validator = new Validator(['vDup' => '1837.40']);

        $this->assertSame('1837.40', $validator->rules('vDup', 'decimal:2'));
        $this->assertNull($validator->errors());
    }

    public function testAceitaFloatEInteiroENormalizaCasas(): void
    {
        $validator = new Validator(['a' => 1837.4, 'b' => 1837, 'c' => '0']);

        $this->assertSame('1837.40', $validator->rules('a', 'decimal:2'));
        $this->assertSame('1837.00', $validator->rules('b', 'decimal:2'));
        $this->assertSame('0.00', $validator->rules('c', 'decimal:2'));
        $this->assertNull($validator->errors());
    }

    public function testIgnoraVazioENulo(): void
    {
        // Vazio/nulo é responsabilidade do `required`/`noEmpty`; decimal não dispara.
        $validator = new Validator(['a' => '', 'b' => null]);

        $this->assertNull($validator->rules('a', 'decimal:2'));
        $this->assertNull($validator->rules('b', 'decimal:2'));
        $this->assertNull($validator->errors());
    }

    public function testRejeitaMaisCasasDecimaisQueOPermitido(): void
    {
        $validator = new Validator(['v' => '1.234']);

        $this->assertNull($validator->rules('v', 'decimal:2'));
        $errors = $validator->errors();
        $this->assertIsArray($errors);
        $this->assertStringContainsString('decimal places', $errors[0]['error']);
    }

    public function testRejeitaMaisDigitosInteirosQueOPermitido(): void
    {
        $validator = new Validator(['v' => '1234567.00']);

        $this->assertNull($validator->rules('v', 'decimal:2,5'));
        $errors = $validator->errors();
        $this->assertIsArray($errors);
        $this->assertStringContainsString('before the decimal point', $errors[0]['error']);
    }

    public function testAceitaNegativoDentroDoLimite(): void
    {
        $validator = new Validator(['v' => '-12.5']);

        $this->assertSame('-12.50', $validator->rules('v', 'decimal:2,5'));
        $this->assertNull($validator->errors());
    }

    public function testPropagaCodigoDeErro(): void
    {
        $validator = new Validator(['vDup' => '1837,40']);
        $validator->rules('vDup', 'decimal:2#DUP001');

        $error = $validator->errors();
        $this->assertSame('DUP001', $error[0]['code']);
    }

    /** Spec 498 — notação exponencial driblava o teto de inteiros. */
    public function testRejeitaNotacaoExponencial(): void
    {
        foreach (['1e15', '1.0E+15', '1E3', '-2e-1'] as $v) {
            $validator = new Validator(['vNF' => $v]);
            $this->assertNull($validator->rules('vNF', 'decimal:2,13'), $v);
            $this->assertIsArray($validator->errors(), $v);
        }
    }

    /** Spec 498 — vUnCom 11v10 não pode ser corrompido pelo float. */
    public function testPrecisaoAlemDe15DigitosPreservada(): void
    {
        $validator = new Validator(['vUnCom' => '12345678901.1234567891', 'qCom' => '99999999999.9999']);

        $this->assertSame('12345678901.1234567891', $validator->rules('vUnCom', 'decimal:10,11'));
        $this->assertSame('99999999999.9999', $validator->rules('qCom', 'decimal:4,11'));
        $this->assertNull($validator->errors());
    }

    /** Spec 498 — número JSON (float) na fronteira do leiaute não vira 12 inteiros. */
    public function testFloatNaFronteiraNaoArredondaParaCima(): void
    {
        $validator = new Validator(['qCom' => 99999999999.9999]);

        $this->assertSame('99999999999.9999', $validator->rules('qCom', 'decimal:4,11'));
        $this->assertNull($validator->errors());
    }

    /** Ruído de ponto flutuante (0.1+0.2) continua aceito como antes. */
    public function testFloatComRuidoContinuaAceito(): void
    {
        $validator = new Validator(['v' => 0.1 + 0.2]);

        $this->assertSame('0.30', $validator->rules('v', 'decimal:2'));
        $this->assertNull($validator->errors());
    }

    /** Formato de saída idêntico ao antigo para entradas válidas. */
    public function testFormatoDeSaidaInalterado(): void
    {
        $casos = [
            ['007.5', 'decimal:2', '7.50'],
            ['-0.00', 'decimal:2', '0.00'],
            ['-12.3', 'decimal:2', '-12.30'],
            ['.5', 'decimal:2', '0.50'],
            ['5.', 'decimal:2', '5.00'],
            [' 5', 'decimal:2', '5.00'],
            ['+5', 'decimal:2', '5.00'],
            [12, 'decimal:4', '12.0000'],
        ];
        foreach ($casos as [$in, $rule, $out]) {
            $validator = new Validator(['v' => $in]);
            $this->assertSame($out, $validator->rules('v', $rule), var_export($in, true));
            $this->assertNull($validator->errors(), var_export($in, true));
        }
    }

    public function testTetoDeTrezeInteirosNoTotal(): void
    {
        $ok = new Validator(['vNF' => '9999999999999.99']);
        $this->assertSame('9999999999999.99', $ok->rules('vNF', 'decimal:2,13'));

        $nok = new Validator(['vNF' => '10000000000000.00']);
        $this->assertNull($nok->rules('vNF', 'decimal:2,13'));
        $this->assertIsArray($nok->errors());
    }
}
