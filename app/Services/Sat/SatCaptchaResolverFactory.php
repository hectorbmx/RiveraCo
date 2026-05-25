<?php

namespace App\Services\Sat;

use App\Services\Sat\Resolvers\DatabaseCaptchaResolver;
use PhpCfdi\ImageCaptchaResolver\CaptchaResolverInterface;
use PhpCfdi\ImageCaptchaResolver\BoxFacturaAI\BoxFacturaAIResolver;
use PhpCfdi\ImageCaptchaResolver\Resolvers\CaptchaLocalResolver;
use PhpCfdi\ImageCaptchaResolver\Resolvers\CommandLineResolver;
use PhpCfdi\ImageCaptchaResolver\Resolvers\MultiResolver;

class SatCaptchaResolverFactory
{
    public function make(string $token): CaptchaResolverInterface
    {
        $resolvers = [];
        $driver = (string) config('services.sat_captcha.driver', 'manual');

        if ($driver === 'boxfactura' || $driver === 'auto') {
            $configFile = storage_path('sat-captcha-ai-model/configs.yaml');
            if (is_file($configFile)) {
                $resolvers[] = BoxFacturaAIResolver::createFromConfigs($configFile);
            }
        }

        if ($driver === 'command' || $driver === 'auto') {
            $command = $this->commandFromConfig();
            if ($command !== []) {
                $resolvers[] = CommandLineResolver::create($command);
            }
        }

        if ($driver === 'local' || $driver === 'auto') {
            $baseUrl = (string) config('services.sat_captcha.local_url', '');
            if ($baseUrl !== '') {
                $resolvers[] = CaptchaLocalResolver::create(
                    $baseUrl,
                    (int) config('services.sat_captcha.local_initial_wait', 1),
                    (int) config('services.sat_captcha.local_timeout', 30),
                    (int) config('services.sat_captcha.local_sleep_ms', 500),
                );
            }
        }

        $resolvers[] = new DatabaseCaptchaResolver(
            token: $token,
            timeoutSeconds: (int) config('services.sat_captcha.manual_timeout', 300),
            pollIntervalSeconds: (int) config('services.sat_captcha.manual_poll_seconds', 3),
        );

        return new MultiResolver(...$resolvers);
    }

    /**
     * @return string[]
     */
    private function commandFromConfig(): array
    {
        $raw = config('services.sat_captcha.command', '');

        if (is_array($raw)) {
            return array_values(array_filter($raw, fn ($part) => is_string($part) && $part !== ''));
        }

        $raw = trim((string) $raw);
        if ($raw === '') {
            return [];
        }

        $json = json_decode($raw, true);
        if (is_array($json)) {
            return array_values(array_filter($json, fn ($part) => is_string($part) && $part !== ''));
        }

        return str_getcsv($raw, ' ');
    }
}
