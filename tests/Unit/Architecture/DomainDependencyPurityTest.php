<?php

declare(strict_types=1);

namespace Silao\Tests\Unit\Architecture;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

final class DomainDependencyPurityTest extends TestCase
{
    public function testDomainAndApplicationDoNotDependOnBlueprintOrSpecificBusinessTerms(): void
    {
        $directoriesToTest = [
            dirname(__DIR__, 3) . '/src/Domain',
            dirname(__DIR__, 3) . '/src/Application',
        ];

        // Specific exception: InstallBlueprintService explicitly orchestrates the bridge
        $allowedExceptions = ['InstallBlueprintService.php', 'ConfigureBookingModelCommand.php', 'InstallBlueprintResultDTO.php'];

        $forbiddenTerms = ['Transfer', 'Vehicle', 'Consultation', 'Taxi', 'Airport'];

        foreach ($directoriesToTest as $dir) {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS)
            );

            /** @var SplFileInfo $file */
            foreach ($iterator as $file) {
                if ($file->getExtension() !== 'php') {
                    continue;
                }

                if (in_array($file->getFilename(), $allowedExceptions, true)) {
                    continue;
                }

                $content = (string) file_get_contents($file->getPathname());

                // 1. Check strict directional dependency (No use Silao\Blueprint)
                $this->assertStringNotContainsString(
                    'use Silao\Blueprint',
                    $content,
                    sprintf('Architectural violation: File "%s" must not depend on the Blueprint layer.', $file->getFilename())
                );

                // 2. Check Domain Purity against Business specific contamination
                foreach ($forbiddenTerms as $term) {
                    $this->assertStringNotContainsString(
                        'class ' . $term,
                        $content,
                        sprintf('Architectural violation: Core file "%s" is contaminated with specific business term "%s".', $file->getFilename(), $term)
                    );
                }
            }
        }
    }
}