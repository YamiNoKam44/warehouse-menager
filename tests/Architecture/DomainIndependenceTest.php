<?php

declare(strict_types=1);

namespace App\Tests\Architecture;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

final class DomainIndependenceTest extends TestCase
{
    public function testDomainDoesNotDependOnFrameworkOrPersistenceLibraries(): void
    {
        $sourceDirectory = realpath(__DIR__.'/../../src');

        self::assertNotFalse($sourceDirectory);

        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($sourceDirectory, RecursiveDirectoryIterator::SKIP_DOTS),
        );

        foreach ($files as $file) {
            $path = str_replace('\\', '/', $file->getPathname());

            if ('php' !== $file->getExtension() || !str_contains($path, '/Domain/')) {
                continue;
            }

            $code = file_get_contents($file->getPathname());

            self::assertIsString($code);
            self::assertDoesNotMatchRegularExpression(
                '/^use\s+(Doctrine|Symfony|Twig)\\\\/m',
                $code,
                sprintf('Warstwa Domain nie może zależeć od vendora: %s', $path),
            );
        }
    }
}
