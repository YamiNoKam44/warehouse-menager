<?php

declare(strict_types=1);

namespace App\Tests\Architecture;

use FilesystemIterator;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

final class DomainIndependenceTest extends TestCase
{
    public function testDomainAndApplicationLayersPointInward(): void
    {
        $sourceDirectory = realpath(__DIR__.'/../../src');

        self::assertNotFalse($sourceDirectory);

        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($sourceDirectory, FilesystemIterator::SKIP_DOTS),
        );

        foreach ($files as $file) {
            $path = str_replace('\\', '/', $file->getPathname());

            if ('php' !== $file->getExtension()) {
                continue;
            }

            $code = file_get_contents($file->getPathname());

            self::assertIsString($code);

            if (str_contains($path, '/Domain/')) {
                self::assertDoesNotMatchRegularExpression(
                    '/^use\s+(?:App\\\\[^;]+\\\\(?:Application|Infrastructure)\\\\|Doctrine\\\\|Psr\\\\|Symfony\\\\|Twig\\\\)/m',
                    $code,
                    sprintf('Domain must remain independent: %s', $path),
                );
            }

            if (str_contains($path, '/Application/')) {
                self::assertDoesNotMatchRegularExpression(
                    '/^use\s+(?:App\\\\[^;]+\\\\Infrastructure\\\\|Doctrine\\\\|Psr\\\\|Symfony\\\\|Twig\\\\)/m',
                    $code,
                    sprintf('Application must not depend on Infrastructure: %s', $path),
                );
            }
        }
    }
}
