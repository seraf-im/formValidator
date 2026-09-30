<?php

declare(strict_types=1);

namespace Tests\Validators;

use PHPUnit\Framework\TestCase;
use Pnhs\FormValidator\Validator;
use Pnhs\FormValidator\validators\validatorDocument;

/**
 * Spec 439 / S1 — CNPJ alfanumérico (IN RFB 2.229/2024).
 *
 * 12 posições [A-Z0-9] + 2 DVs numéricos; DV módulo 11 com valor de cada
 * caractere = ASCII − 48 (dígitos 0..9, letras A..Z = 17..42), pesos
 * 5..2,9..2 (DV1) e 6..2,9..2 (DV2).
 *
 * Os vetores alfanuméricos abaixo foram calculados por um script Python
 * INDEPENDENTE (não pela rotina PHP testada) — evita teste tautológico.
 * 12ABC34501DE35 é o exemplo oficial da Receita Federal.
 */
class validatorDocumentTest extends TestCase
{
    private function rule($valor, string $rule)
    {
        $validator = new Validator(['doc' => $valor]);
        $result = $validator->rules('doc', $rule);

        return [$result, $validator->errors()];
    }

    /**
     * @dataProvider cnpjsValidos
     */
    public function testAceitaCnpjValido(string $cnpj, string $esperado): void
    {
        foreach (['document:cnpj', 'document:cpf_cnpj'] as $rule) {
            [$result, $errors] = $this->rule($cnpj, $rule);

            $this->assertNull($errors, "{$rule} reprovou {$cnpj}");
            $this->assertSame($esperado, $result, "{$rule} devolveu valor inesperado para {$cnpj}");
        }
    }

    public function cnpjsValidos(): array
    {
        return [
            // Legado numérico: devolve o valor ORIGINAL intacto (sem mudança).
            'numerico cru'                 => ['11222333000181', '11222333000181'],
            'numerico com mascara'         => ['11.222.333/0001-81', '11.222.333/0001-81'],
            // Alfanumérico — exemplo oficial RFB.
            'alfanumerico oficial'         => ['12ABC34501DE35', '12ABC34501DE35'],
            'alfanumerico com mascara'     => ['12.ABC.345/01DE-35', '12.ABC.345/01DE-35'],
            // Minúsculo é aceito e devolvido em MAIÚSCULO (formato oficial).
            'alfanumerico minusculo'       => ['12abc34501de35', '12ABC34501DE35'],
            'minusculo com mascara'        => ['12.abc.345/01de-35', '12.ABC.345/01DE-35'],
            'com espacos'                  => [' 12 ABC 345 01DE 35 ', ' 12 ABC 345 01DE 35 '],
            // Vetores extras (script Python independente).
            'alfanumerico intercalado'     => ['A1B2C3D4E5F668', 'A1B2C3D4E5F668'],
            'raiz so letras'               => ['ABCDEFGH000195', 'ABCDEFGH000195'],
            // Base só com Z não é "sequência de um único caractere" (DVs 62).
            'base toda Z'                  => ['ZZZZZZZZZZZZ62', 'ZZZZZZZZZZZZ62'],
        ];
    }

    /**
     * @dataProvider cnpjsInvalidos
     */
    public function testRejeitaCnpjInvalidoComSentinela(string $cnpj): void
    {
        $validator = new Validator(['doc' => $cnpj]);
        $result = $validator->rules('doc', 'document:cnpj#77');

        // Contrato da lib: reprovação só vale com "_false" → Validator devolve
        // null e registra o erro (mensagem/código inalterados).
        $this->assertNull($result, "document:cnpj aceitou {$cnpj}");
        $errors = $validator->errors();
        $this->assertIsArray($errors);
        $this->assertSame('doc', $errors[0]['parameter']);
        $this->assertSame('document', $errors[0]['type']);
        $this->assertSame('77', $errors[0]['code']);
        $this->assertSame('doc O valor informado não é um CNPJ válido.', $errors[0]['error']);
    }

    public function cnpjsInvalidos(): array
    {
        return [
            'dv1 errado alfanumerico'   => ['12ABC34501DE45'],
            'dv2 errado alfanumerico'   => ['12ABC34501DE36'],
            'dv errado numerico'        => ['11222333000182'],
            'letra no dv1'              => ['12ABC34501DEA5'],
            'letra no dv2'              => ['12ABC34501DE3A'],
            'letras nos 2 dvs'          => ['12ABC34501DEAB'],
            'sequencia zeros'           => ['00000000000000'],
            'sequencia uns'             => ['11111111111111'],
            'curto'                     => ['12ABC34501DE3'],
            'longo'                     => ['12ABC34501DE355'],
            // Minúsculo com DV errado continua errado.
            'minusculo dv errado'       => ['12abc34501de36'],
            // Caractere não ASCII não vira letra válida (é descartado → 13 pos.).
            'acentuado'                 => ['12ÁBC34501DE35'],
            // Mudança consciente: letra solta junto a um CNPJ numérico agora é
            // significativa (o legado a descartava com \D).
            'letra extra em numerico'   => ['X11222333000181'],
        ];
    }

    public function testCpfContinuaSoNumerico(): void
    {
        [$result, $errors] = $this->rule('529.982.247-25', 'document:cpf');
        $this->assertNull($errors);
        $this->assertSame('529.982.247-25', $result);

        [$result, $errors] = $this->rule('52998224725', 'document:cpf_cnpj');
        $this->assertNull($errors);
        $this->assertSame('52998224725', $result);

        [$result, $errors] = $this->rule('52998224726', 'document:cpf');
        $this->assertNull($result);
        $this->assertSame('doc O valor informado não é um CPF válido.', $errors[0]['error']);

        [$result, $errors] = $this->rule('11111111111', 'document:cpf');
        $this->assertNull($result);
    }

    public function testCpfCnpjRejeitaCpfComLetra(): void
    {
        // 11 posições alfanuméricas NÃO são CPF (CPF segue só numérico).
        [$result, $errors] = $this->rule('5299822472A', 'document:cpf_cnpj');

        $this->assertNull($result);
        $this->assertSame('doc O valor informado não é um CPF ou CNPJ válido.', $errors[0]['error']);
    }

    public function testCnpjAlfanumericoNaoPassaComoCpf(): void
    {
        [$result, $errors] = $this->rule('12ABC34501DE35', 'document:cpf');

        $this->assertNull($result);
        $this->assertSame('document', $errors[0]['type']);
    }

    public function testVazioENuloNaoDisparam(): void
    {
        [$result, $errors] = $this->rule('', 'document:cnpj');
        $this->assertSame('', $result);
        $this->assertNull($errors);

        [$result, $errors] = $this->rule(null, 'document:cnpj');
        $this->assertNull($result);
        $this->assertNull($errors);
    }

    public function testOpcaoInvalidaDevolveSentinela(): void
    {
        $model = new validatorDocument();
        $model->setValue('12ABC34501DE35');
        $model->setOption('rg');

        $this->assertSame('_false', $model->execute());
    }

    public function testExecuteDevolveSentinelaStringNaoBool(): void
    {
        $model = new validatorDocument();
        $model->setValue('12ABC34501DEAB');
        $model->setOption('cnpj');

        $this->assertSame('_false', $model->execute());
    }

    public function testRotinaDeDvBateComVetoresIndependentes(): void
    {
        $this->assertSame('35', validatorDocument::cnpjCheckDigits('12ABC34501DE'));
        $this->assertSame('68', validatorDocument::cnpjCheckDigits('A1B2C3D4E5F6'));
        $this->assertSame('81', validatorDocument::cnpjCheckDigits('112223330001'));
        $this->assertSame(17, validatorDocument::charValue('A'));
        $this->assertSame(42, validatorDocument::charValue('Z'));
        $this->assertSame(0, validatorDocument::charValue('0'));
    }
}
