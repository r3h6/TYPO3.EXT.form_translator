<?php

declare(strict_types=1);

namespace R3H6\FormTranslator\Tests\Unit\Service;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use R3H6\FormTranslator\Service\TranslationFileService;

class TranslationFileServiceTest extends TestCase
{
    #[Test]
    #[DataProvider('fileNames')]
    public function derivesLanguageFromFileName(string $fileName, string $baseFileName, ?string $expected): void
    {
        self::assertSame($expected, TranslationFileService::getLanguageFromFileName($fileName, $baseFileName));
    }

    /**
     * @return iterable<string, array{string, string, ?string}>
     */
    public static function fileNames(): iterable
    {
        yield 'shared base' => ['locallang.xlf', 'locallang.xlf', 'default'];
        yield 'shared language' => ['de.locallang.xlf', 'locallang.xlf', 'de'];
        yield 'shared country language' => ['de_CH.locallang.xlf', 'locallang.xlf', 'de_CH'];
        yield 'form base' => ['example.xlf', 'example.xlf', 'default'];
        yield 'form language' => ['de.example.xlf', 'example.xlf', 'de'];
        yield 'unrelated file' => ['other.xlf', 'example.xlf', null];
        yield 'invalid language part' => ['../example.xlf', 'example.xlf', null];
    }
}
