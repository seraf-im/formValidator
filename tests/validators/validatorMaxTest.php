<?php

declare(strict_types=1);

namespace Tests\Validators;

use PHPUnit\Framework\TestCase;
use Pnhs\FormValidator\Validator;

/**
 * Regressão: validatorMax reprovava devolvendo `false` (bool) em vez da
 * sentinela "_false" (string). Validator::validators() só registra erro com
 * `=== "_false"`, então a regra `max:` NUNCA bloqueava — falha ABERTA: o valor
 * acima do limite seguia adiante como `false` e o Model castava pra 0.
 *
 * Caso real: services/sale, CompanySetting `couvert_percentage` com
 * 'numeric|max:100' — um couvert de 150% era aceito e gravado como 0.
 */
class validatorMaxTest extends TestCase
{
    public function testRejeitaValorAcimaDoMaximo(): void
    {
        $validator = new Validator(['couvert_percentage' => '150']);
        $result = $validator->rules('couvert_percentage', 'max:100');

        $this->assertNull($result);
        $errors = $validator->errors();
        $this->assertIsArray($errors);
        $this->assertSame('couvert_percentage', $errors[0]['parameter']);
        $this->assertSame('max', $errors[0]['type']);
    }

    public function testAceitaValorDentroDoLimite(): void
    {
        $validator = new Validator(['couvert_percentage' => '12.5']);

        $this->assertSame(12.5, $validator->rules('couvert_percentage', 'max:100'));
        $this->assertNull($validator->errors());
    }

    public function testAceitaValorExatamenteNoLimite(): void
    {
        // Fronteira: `max` é inclusivo (reprova só `>`, não `>=`).
        $validator = new Validator(['couvert_percentage' => '100']);

        $this->assertSame(100.0, $validator->rules('couvert_percentage', 'max:100'));
        $this->assertNull($validator->errors());
    }

    public function testRejeitaEmCadeiaComNumeric(): void
    {
        // Cadeia real usada em services/sale: 'numeric|max:100'.
        $validator = new Validator(['couvert_percentage' => '150']);

        $this->assertNull($validator->rules('couvert_percentage', 'numeric|max:100'));
        $this->assertSame('max', $validator->errors()[0]['type']);
    }

    public function testPropagaCodigoDeErro(): void
    {
        $validator = new Validator(['couvert_percentage' => '150']);
        $validator->rules('couvert_percentage', 'max:100#65');

        $this->assertEquals(65, $validator->errors()[0]['code']);
    }

    public function testVazioNaoDisparaMasDevolveZero(): void
    {
        // Caracterização do comportamento ATUAL (não é o ideal): setValue faz
        // (string) do valor, então null/'' viram 0.0 e passam. Quem precisa
        // barrar ausência usa `required`/`noEmpty`. Fixado em teste para que
        // uma mudança nesse cast seja consciente, não acidental.
        $validator = new Validator(['couvert_percentage' => '']);

        $this->assertSame(0.0, $validator->rules('couvert_percentage', 'max:100'));
        $this->assertNull($validator->errors());
    }
}
