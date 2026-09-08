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
}
