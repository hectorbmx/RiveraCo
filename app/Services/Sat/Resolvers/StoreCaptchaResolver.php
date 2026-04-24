<?php

namespace App\Services\Sat\Resolvers;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpCfdi\ImageCaptchaResolver\CaptchaAnswer;
use PhpCfdi\ImageCaptchaResolver\CaptchaAnswerInterface;
use PhpCfdi\ImageCaptchaResolver\CaptchaImageInterface;
use PhpCfdi\ImageCaptchaResolver\CaptchaResolverInterface;
use RuntimeException;

class StoreCaptchaResolver implements CaptchaResolverInterface
{
    public function __construct(
        private readonly ?string $captchaAnswer = null,
        private readonly string $disk = 'public',
    ) {
    }

    public function resolve(CaptchaImageInterface $image): CaptchaAnswerInterface
    {
        if (null !== $this->captchaAnswer && '' !== trim($this->captchaAnswer)) {
            return new CaptchaAnswer(trim($this->captchaAnswer));
        }

        $path = 'sat/captcha/captcha_' . Str::uuid() . '.png';

        Storage::disk($this->disk)->put($path, $image->asBinary());

        throw new RuntimeException("CAPTCHA_REQUIRED|{$path}");
    }
}