<?php

declare(strict_types=1);

namespace Tests\Validators;

use PHPUnit\Framework\TestCase;
use Pnhs\FormValidator\Validator;

/**
 * Regressão (spec 487): phone:BR reprovava devolvendo bool `false`, que o
 * Validator não registra como erro — o PATCH respondia 200 e gravava '' no
 * telefone. Também: fixo de 10 dígitos só aceitava 3º dígito 2/3; fixos
 * válidos (Anatel) começam com 2, 3, 4 ou 5.
 */
class validatorPhoneTest extends TestCase
{
    private function run1($value, string $rule = 'phone:BR,false')
    {
        $v = new Validator(['n' => $value]);
        $r = $v->rules('n', $rule);
        return [$r, $v->errors()];
    }

    /** País vazio (PATCH com number sem country) ou desconhecido: erro, não Error 500. */
    public function testPaisVazioOuDesconhecidoRegistraErro(): void
    {
        foreach (['phone:,false', 'phone:XX,false'] as $rule) {
            [$r, $e] = $this->run1('11987654321', $rule);
            $this->assertNull($r, $rule);
            $this->assertIsArray($e, $rule);
            $this->assertSame('n', $e[0]['parameter'], $rule);
        }
    }

    public function testCelularValido(): void
    {
        [$r, $e] = $this->run1('11987654321');
        $this->assertSame('11987654321', $r);
        $this->assertNull($e);
    }

    /** @dataProvider fixosValidos */
    public function testFixoValido(string $n): void
    {
        [$r, $e] = $this->run1($n);
        $this->assertSame($n, $r);
        $this->assertNull($e);
    }

    public function fixosValidos(): array
    {
        return ['2' => ['1122345678'], '3' => ['1133345678'], '4' => ['1144345678'], '5' => ['1155345678']];
    }

    /** @dataProvider fixosInvalidos */
    public function testFixoInvalidoRegistraErro(string $n): void
    {
        [$r, $e] = $this->run1($n);
        $this->assertNull($r);
        $this->assertIsArray($e);
        $this->assertSame('n', $e[0]['parameter']);
        $this->assertSame('phone', $e[0]['type']);
        $this->assertStringContainsString('is not valid', $e[0]['message'] ?? json_encode($e));
    }

    public function fixosInvalidos(): array
    {
        return [
            '1' => ['1113345678'], '6' => ['1163345678'], '7' => ['1173345678'],
            '8' => ['1183345678'], '9 com 10 dig' => ['1193345678'], '0' => ['1103345678'],
        ];
    }

    /** @dataProvider invalidosGerais */
    public function testInvalidoRegistraErro(string $n): void
    {
        [$r, $e] = $this->run1($n);
        $this->assertNull($r);
        $this->assertNotNull($e);
    }

    public function invalidosGerais(): array
    {
        return [
            'com DDI' => ['5511987654321'], 'sem DDD (9 dig)' => ['987654321'],
            'sem DDD (8 dig)' => ['33345678'], 'curto' => ['1'], '11 dig sem 9' => ['11887654321'],
        ];
    }

    public function testServicosContinuamValidos(): void
    {
        foreach (['03001234567', '08001234567', '05001234567', '09001234567', '40031234', '40041234'] as $n) {
            [$r, $e] = $this->run1($n);
            $this->assertSame($n, $r, $n);
            $this->assertNull($e, $n);
        }
    }

    public function testRetornoDetalhado(): void
    {
        [$r] = $this->run1('1144345678', 'phone:BR,true');
        $this->assertSame('landline', $r['type']);
        $this->assertSame('(11) 4434 - 5678', $r['formatted']);
        $this->assertSame('1144345678', $r['number']);
    }

    public function testVazioNuloEAusenteNaoValidam(): void
    {
        foreach ([null, ''] as $val) {
            [$r, $e] = $this->run1($val);
            $this->assertNull($r);
            $this->assertNull($e);
        }
        $v = new Validator([]);
        $this->assertNull($v->rules('n', 'phone:BR,false'));
        $this->assertNull($v->errors());
    }

    public function testCodigoDeErroPropagado(): void
    {
        $v = new Validator(['n' => '1']);
        $v->rules('n', 'phone:BR,false#E123');
        $this->assertSame('E123', $v->errors()[0]['code']);
    }
}
