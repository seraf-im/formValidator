<?php

declare(strict_types=1);

namespace Tests\Validators;

use PHPUnit\Framework\TestCase;
use Pnhs\FormValidator\Validator;

/**
 * Regressão: validatorMin_len retornava `false` (bool) em vez de "_false"
 * (string) no ramo de falha, e o Validator compara `=== "_false"` — então o
 * erro nunca era registrado (falha silenciosa). Spec 073: motivado por endereço
 * de NF-e com logradouro "B" (1 char) que a SEFAZ rejeita (xLgr minLength 2).
 */
class validatorMin_lenTest extends TestCase
{
    public function testRejeitaValorMenorQueMinimo(): void
    {
        $validator = new Validator(['street' => 'B']);
        $result = $validator->rules('street', 'min_len:2');

        $this->assertNull($result);
        $errors = $validator->errors();
        $this->assertIsArray($errors);
        $this->assertSame('street', $errors[0]['parameter']);
        $this->assertSame('min_len', $errors[0]['type']);
    }

    public function testAceitaValorComTamanhoMinimo(): void
    {
        $validator = new Validator(['street' => 'Rua B']);

        $this->assertSame('Rua B', $validator->rules('street', 'min_len:2'));
        $this->assertNull($validator->errors());
    }

    public function testIgnoraVazio(): void
    {
        // Vazio é responsabilidade do `required`/`noEmpty`; min_len não dispara.
        $validator = new Validator(['street' => '']);

        $this->assertNull($validator->rules('street', 'min_len:2'));
        $this->assertNull($validator->errors());
    }

    public function testPropagaCodigoDeErro(): void
    {
        $validator = new Validator(['street' => 'B']);
        $validator->rules('street', 'min_len:2#73');

        $error = $validator->errors();
        $this->assertEquals(73, $error[0]['code']);
    }
}
