<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Laravel\Fortify\Features;
use Laravel\Passport\Passport;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->usarChavesDoPassportDosTestes();
    }

    protected function skipUnlessFortifyHas(string $feature, ?string $message = null): void
    {
        if (! Features::enabled($feature)) {
            $this->markTestSkipped($message ?? "Fortify feature [{$feature}] is not enabled.");
        }
    }

    /**
     * Chaves que assinam e conferem os tokens do Passport (API REST e MCP),
     * só para os testes, numa pasta temporária. As chaves reais
     * (storage/oauth-*.key) ficam fora do repositório e não existem no CI.
     * São geradas na primeira execução e reaproveitadas nas seguintes.
     */
    private function usarChavesDoPassportDosTestes(): void
    {
        $pasta = sys_get_temp_dir().DIRECTORY_SEPARATOR.'projeto-tcc-passport-testes';
        Passport::loadKeysFrom($pasta);

        if (! is_file(Passport::keyPath('oauth-private.key'))) {
            File::ensureDirectoryExists($pasta);
            Artisan::call('passport:keys', ['--length' => 2048, '--force' => true]);
        }
    }
}
