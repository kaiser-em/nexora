<?php

declare(strict_types=1);

namespace Silao\Blueprint\Registry;

use Silao\Blueprint\Contract\BlueprintRegistryInterface;
use Silao\Blueprint\Exception\BlueprintNotFoundException;
use Silao\Blueprint\Exception\InvalidBlueprintException;
use Silao\Blueprint\Validator\BlueprintSchemaValidator;
use Silao\Blueprint\ValueObject\BlueprintSummary;

final readonly class FileBlueprintRegistry implements BlueprintRegistryInterface
{
    private string $directory;

    public function __construct()
    {
        $this->directory = dirname(__DIR__, 3) . '/blueprints';
    }

    /**
     * @return array<BlueprintSummary>
     */
    public function all(): array
    {
        $blueprints = [];
        if (!is_dir($this->directory)) {
            return [];
        }

        $files = glob($this->directory . '/*.json');
        if ($files === false) {
            return [];
        }

        foreach ($files as $file) {
            try {
                $content = (string) file_get_contents($file);
                $data = json_decode($content, true, 512, JSON_THROW_ON_ERROR);

                if (is_array($data) && isset($data['id'], $data['version'], $data['name'], $data['description'], $data['category'])) {
                    $blueprints[] = new BlueprintSummary(
                        (string) $data['id'],
                        (string) $data['version'],
                        (string) $data['name'],
                        (string) $data['description'],
                        (string) $data['category']
                    );
                }
            } catch (\Throwable) {
                // Silently ignore corrupted files in 'all' listing
            }
        }

        return $blueprints;
    }

    /**
     * @return array<string, mixed>
     * @throws BlueprintNotFoundException
     * @throws InvalidBlueprintException
     */
    public function getRawDefinition(string $id): array
    {
        $file = $this->directory . '/' . preg_replace('/[^a-z0-9_-]/', '', $id) . '.json';

        if (!file_exists($file)) {
            throw new BlueprintNotFoundException(sprintf('Blueprint "%s" not found.', $id));
        }

        try {
            $content = (string) file_get_contents($file);
            $data = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable $e) {
            throw new InvalidBlueprintException('Blueprint JSON is malformed: ' . $e->getMessage(), 0, $e);
        }

        if (!is_array($data)) {
            throw new InvalidBlueprintException('Blueprint root must be a JSON object.');
        }

        BlueprintSchemaValidator::validate($data);

        return $data;
    }
}