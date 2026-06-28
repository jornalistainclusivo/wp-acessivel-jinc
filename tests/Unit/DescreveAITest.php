<?php declare(strict_types=1);

namespace WpAcessivelJinc\Tests\Unit;

use PHPUnit\Framework\TestCase;
use WpAcessivelJinc\Modules\MediaGatekeeper\DescreveAIClientInterface;
use WpAcessivelJinc\Modules\MediaGatekeeper\DescreveAIResult;
use WpAcessivelJinc\Modules\MediaGatekeeper\NullDescreveAIClient;

/**
 * @spec-source docs/SPEC_MediaGatekeeper.md
 * @covers \WpAcessivelJinc\Modules\MediaGatekeeper\NullDescreveAIClient
 * @covers \WpAcessivelJinc\Modules\MediaGatekeeper\DescreveAIResult
 */
class DescreveAITest extends TestCase
{
    /** @test */
    public function null_client_implements_interface(): void
    {
        $client = new NullDescreveAIClient();
        $this->assertInstanceOf(DescreveAIClientInterface::class, $client);
    }

    /** @test */
    public function null_client_always_returns_null(): void
    {
        $client = new NullDescreveAIClient();

        $result = $client->generateAltText(42, 'https://example.com/photo.jpg');
        $this->assertNull($result);
    }

    /** @test */
    public function result_value_object_stores_data_correctly(): void
    {
        $result = new DescreveAIResult(
            altText: 'Fotografia de um gato preto',
            confidence: 0.95,
            model: 'descreve-v2',
            language: 'pt-BR',
        );

        $this->assertSame('Fotografia de um gato preto', $result->altText);
        $this->assertSame(0.95, $result->confidence);
        $this->assertSame('descreve-v2', $result->model);
        $this->assertSame('pt-BR', $result->language);
    }
}
