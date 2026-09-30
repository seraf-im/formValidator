<?php

declare(strict_types=1);

namespace Tests\Validators;

use PHPUnit\Framework\TestCase;
use Pnhs\FormValidator\Validator;
use Pnhs\FormValidator\validators\validatorAccessKeyInvoiceBR;
use Pnhs\FormValidator\validators\validatorDocument;

/**
 * Spec 439 / S1 — chave de acesso com CNPJ alfanumérico (NT Conjunta 2025.001).
 *
 * Formato: ^[0-9]{6}[A-Z0-9]{12}[0-9]{26}$ — letras só nas 12 primeiras
 * posições do CNPJ do emitente (posições 7–18). cDV: módulo 11 sobre as 43
 * primeiras posições, pesos 2..9 da direita, cada caractere = ASCII − 48.
 *
 * Vetores HARDCODED calculados por script Python independente:
 *  - 31 2607 12ABC34501DE35 55 001 000000123 1 12345678 → cDV 8
 *  - 31 2607 A1B2C3D4E5F668 65 002 000004567 1 87654321 → cDV 3
 *  - 35 2406 11222333000181 55 001 000000001 1 12345678 → cDV 6 (numérica,
 *    conferida também contra o validador LEGADO antes da mudança)
 */
class validatorAccessKeyInvoiceBRTest extends TestCase
{
    private const CHAVE_ALFA = '31260712ABC34501DE35550010000001231123456788';
    private const CHAVE_ALFA_NFCE = '312607A1B2C3D4E5F668650020000045671876543213';
    private const CHAVE_NUMERICA = '35240611222333000181550010000000011123456786';

    private function rule($valor)
    {
        $validator = new Validator(['chave' => $valor]);
        $result = $validator->rules('chave', 'accessKeyInvoiceBR#88');

        return [$result, $validator->errors()];
    }

    /**
     * @dataProvider chavesValidas
     */
    public function testAceitaChaveValida(string $chave, string $esperado): void
    {
        [$result, $errors] = $this->rule($chave);

        $this->assertNull($errors, "reprovou {$chave}");
        $this->assertSame($esperado, $result);
    }

    public function chavesValidas(): array
    {
        return [
            'numerica legado'          => [self::CHAVE_NUMERICA, self::CHAVE_NUMERICA],
            'alfanumerica nfe'         => [self::CHAVE_ALFA, self::CHAVE_ALFA],
            'alfanumerica nfce'        => [self::CHAVE_ALFA_NFCE, self::CHAVE_ALFA_NFCE],
            'alfanumerica minuscula'   => [strtolower(self::CHAVE_ALFA), self::CHAVE_ALFA],
            'com espacos'              => ['3126 0712 ABC3 4501 DE35 5500 1000 0001 2311 2345 6788',
                                           '3126 0712 ABC3 4501 DE35 5500 1000 0001 2311 2345 6788'],
            // "Id" do XML: o legado aceitava o prefixo (apagava não-dígitos).
            'prefixo NFe numerica'     => ['NFe' . self::CHAVE_NUMERICA, 'NFe' . self::CHAVE_NUMERICA],
            'prefixo NFe alfanumerica' => ['NFe' . self::CHAVE_ALFA, 'NFE' . self::CHAVE_ALFA],
        ];
    }

    public function testChaveMontadaProgramaticamente(): void
    {
        // Monta com o DV do CNPJ + o cDV da própria lib e confere com o vetor
        // hardcoded (independente): as duas rotinas precisam concordar.
        $cnpj = 'A1B2C3D4E5F6' . validatorDocument::cnpjCheckDigits('A1B2C3D4E5F6');
        $corpo = '31' . '2607' . $cnpj . '65' . '002' . '000004567' . '1' . '87654321';
        $chave = $corpo . validatorAccessKeyInvoiceBR::computeDv($corpo);

        $this->assertSame(self::CHAVE_ALFA_NFCE, $chave);
        [$result, $errors] = $this->rule($chave);
        $this->assertNull($errors);
        $this->assertSame($chave, $result);
    }

    /**
     * @dataProvider chavesInvalidas
     */
    public function testRejeitaChaveInvalida(string $chave, string $mensagem): void
    {
        [$result, $errors] = $this->rule($chave);

        $this->assertNull($result, "aceitou {$chave}");
        $this->assertIsArray($errors);
        $this->assertSame('accessKeyInvoiceBR', $errors[0]['type']);
        $this->assertSame('88', $errors[0]['code']);
        $this->assertSame('chave ' . $mensagem, $errors[0]['error']);
    }

    public function chavesInvalidas(): array
    {
        $dv = 'O Dígito Verificador da Chave de Acesso é inválido.';
        $inv = 'A Chave de Acesso informada é inválida.';
        $len = 'A Chave de Acesso deve conter exatamente 44 dígitos numéricos.';

        return [
            'cdv errado alfanumerica'    => [substr(self::CHAVE_ALFA, 0, 43) . '9', $dv],
            'cdv errado numerica'        => [substr(self::CHAVE_NUMERICA, 0, 43) . '7', $dv],
            // Troca de 1 letra no CNPJ sem recalcular → cDV não bate.
            'letra trocada no cnpj'      => ['31260712ABD34501DE35550010000001231123456788', $dv],
            'letra no uf'                => ['3A260712ABC34501DE35550010000001231123456788', $inv],
            'letra nos dvs do cnpj'      => ['31260712ABC34501DEA5550010000001231123456788', $inv],
            'letra no numero da nota'    => ['31260712ABC34501DE3555001000000A231123456788', $inv],
            'letra no cdv'               => ['31260712ABC34501DE3555001000000123112345678X', $inv],
            'sequencia de zeros'         => [str_repeat('0', 44), $inv],
            'curta'                      => [substr(self::CHAVE_ALFA, 0, 43), $len],
            'longa'                      => [self::CHAVE_ALFA . '1', $len],
        ];
    }

    public function testExecuteDevolveSentinelaStringNaoBool(): void
    {
        $model = new validatorAccessKeyInvoiceBR();
        $model->setValue(substr(self::CHAVE_ALFA, 0, 43) . '9');
        $model->setOption('');

        $this->assertSame('_false', $model->execute());
    }

    public function testVazioENuloNaoDisparam(): void
    {
        [$result, $errors] = $this->rule('');
        $this->assertSame('', $result);
        $this->assertNull($errors);

        [$result, $errors] = $this->rule(null);
        $this->assertNull($result);
        $this->assertNull($errors);
    }
}
