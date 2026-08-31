<?php

declare(strict_types=1);

namespace Silao\Tests\Unit\Domain\Integration;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

final class DomainPurityTest extends TestCase
{
    private const array FORBIDDEN_TOKENS = [
        '$wpdb',
        'wp_',
        'WP_',
        'get_post_meta',
        'update_post_meta',
        'get_option',
        'update_option',
        'add_action',
        'do_action',
        'apply_filters',
        'sanitize_text_field',
        'esc_html',
        'wp_mail',
    ];

    public function testDomainClassesHaveStrictTypesAndZeroWordPressDependencies(): void
    {
        $domainDir = dirname(__DIR__, 4) . '/src/Domain';
        $this->assertDirectoryExists($domainDir);

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($domainDir, RecursiveDirectoryIterator::SKIP_DOTS)
        );

        /** @var SplFileInfo $file */
        foreach ($iterator as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $content = (string) file_get_contents($file->getPathname());

            // 1. Strict types declaration
            $this->assertStringContainsString(
                'declare(strict_types=1);',
                $content,
                sprintf('File "%s" is missing declare(strict_types=1);', $file->getFilename())
            );

            // 2. Forbidden WordPress tokens
            foreach (self::FORBIDDEN_TOKENS as $token) {
                $this->assertStringNotContainsString(
                    $token,
                    $content,
                    sprintf('Domain purity violated in "%s": found forbidden token "%s".', $file->getFilename(), $token)
                );
            }
        }
    }
}