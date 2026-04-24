<?php

namespace App\Services\Sat\Resolvers;

use App\Models\SatCaptchaSession;
use PhpCfdi\ImageCaptchaResolver\CaptchaAnswer;
use PhpCfdi\ImageCaptchaResolver\CaptchaAnswerInterface;
use PhpCfdi\ImageCaptchaResolver\CaptchaImageInterface;
use PhpCfdi\ImageCaptchaResolver\CaptchaResolverInterface;
use PhpCfdi\ImageCaptchaResolver\UnableToResolveCaptchaException;

class DatabaseCaptchaResolver implements CaptchaResolverInterface
{
    public function __construct(
        private readonly string $token,
        private readonly int $timeoutSeconds = 120,
        private readonly int $pollIntervalSeconds = 3,
    ) {
    }

    public function resolve(CaptchaImageInterface $image): CaptchaAnswerInterface
    {
        // Guarda la imagen para que la UI la pueda leer
        SatCaptchaSession::create([
            'token'           => $this->token,
            'image_inline_html' => $image->asInlineHtml(),
            'answer'          => null,
            'answered'        => false,
            'expires_at'      => now()->addSeconds($this->timeoutSeconds),
        ]);

        // Bloquea y espera hasta que el usuario responda
        $waited = 0;
        while ($waited < $this->timeoutSeconds) {
            sleep($this->pollIntervalSeconds);
            $waited += $this->pollIntervalSeconds;

            $session = SatCaptchaSession::find($this->token);

            if ($session && $session->answered) {
                $answer = $session->answer;
                $session->delete();
                return new CaptchaAnswer($answer);
            }
        }

        // Timeout: limpia y lanza excepción que el scraper entiende
        SatCaptchaSession::where('token', $this->token)->delete();

        throw new UnableToResolveCaptchaException(
            $this,
            $image,
            new \RuntimeException("Timeout esperando respuesta del captcha (token: {$this->token})")
        );
    }
}