<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;

final class CreateLandingScriptTest extends TestCase
{
    private string $rootPath;

    protected function setUp(): void
    {
        $this->rootPath = sys_get_temp_dir() . '/create-landing-' . bin2hex(random_bytes(4));
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->rootPath);
    }

    public function testCreateLandingListsPresetSlugs(): void
    {
        $script = dirname(__DIR__) . '/scripts/create-landing.sh';
        $command = 'bash ' . escapeshellarg($script) . ' --list-presets';

        exec($command, $output, $exitCode);
        $text = implode("\n", $output);

        self::assertSame(0, $exitCode, $text);
        self::assertStringContainsString('slug', $text);
        self::assertStringContainsString('pediatria', $text);
        self::assertStringContainsString('family', $text);
        self::assertStringContainsString('MedicalClinic', $text);
    }

    public function testCreateLandingUsesSlugContentAndPrunesOtherNiches(): void
    {
        $projectRoot = dirname(__DIR__);
        $target = $this->rootPath . '/pediatria';
        $script = $projectRoot . '/scripts/create-landing.sh';

        $command = 'bash ' . escapeshellarg($script)
            . ' pediatria --target ' . escapeshellarg($target);

        exec($command, $output, $exitCode);

        self::assertSame(0, $exitCode, implode("\n", $output));
        self::assertFileExists($target . '/config/content/landing.php');
        self::assertFileExists($target . '/config/content/pediatria.php');
        self::assertStringContainsString('APP_CONTENT_FILE="pediatria"', (string) file_get_contents($target . '/.env'));
        self::assertStringContainsString('APP_SLUG="pediatria"', (string) file_get_contents($target . '/.env'));
        self::assertStringContainsString('APP_WHATSAPP_NUMBER="5584996360721"', (string) file_get_contents($target . '/.env'));
        self::assertStringContainsString('APP_WHATSAPP_MESSAGE="Oi! Quero conversar sobre o projeto de uma landing page com a NatalCode."', (string) file_get_contents($target . '/.env'));
        self::assertFileExists($target . '/public/assets/img/hero/pediatria-640.webp');
        self::assertFileExists($target . '/public/assets/img/hero/pediatria-mobile-640.webp');
        self::assertFileExists($target . '/public/assets/img/social/pediatria-og.jpg');
        self::assertFileDoesNotExist($target . '/public/assets/img/hero/medico-640.webp');

        $validateCommand = escapeshellarg(PHP_BINARY)
            . ' ' . escapeshellarg($target . '/scripts/validate-landing-content.php')
            . ' --project-root ' . escapeshellarg($target)
            . ' --content pediatria'
            . ' --slug pediatria'
            . ' --strict';

        exec($validateCommand, $validateOutput, $validateExitCode);

        self::assertSame(0, $validateExitCode, implode("\n", $validateOutput));
    }

    private function removeDirectory(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }

        $items = scandir($path);
        if ($items === false) {
            return;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $fullPath = $path . '/' . $item;
            if (is_dir($fullPath)) {
                $this->removeDirectory($fullPath);
                continue;
            }

            @unlink($fullPath);
        }

        @rmdir($path);
    }
}
