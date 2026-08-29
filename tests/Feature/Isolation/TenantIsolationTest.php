<?php

declare(strict_types=1);

namespace Tests\Feature\Isolation;

use App\Shared\Traits\BelongsToOrganization;
use PHPUnit\Framework\Attributes\Test;
use ReflectionClass;
use Tests\TestCase;

class TenantIsolationTest extends TestCase
{
    #[Test]
    public function all_tenant_models_use_belongs_to_organization_trait(): void
    {
        $domainModelsPath = app_path('Domain');
        $models = [];

        if (! is_dir($domainModelsPath)) {
            $this->markTestSkipped('Domain models directory does not exist yet.');
        }

        $directories = glob($domainModelsPath.'/*/Models', GLOB_ONLYDIR);

        foreach ($directories as $directory) {
            $files = glob($directory.'/*.php');

            foreach ($files as $file) {
                $className = $this->getClassNameFromFile($file);

                if ($className === null) {
                    continue;
                }

                if (! class_exists($className)) {
                    continue;
                }

                $reflection = new ReflectionClass($className);

                // Skip abstract classes, interfaces, and the Organization model itself
                if ($reflection->isAbstract() || $reflection->isInterface()) {
                    continue;
                }

                $shortName = $reflection->getShortName();

                if ($shortName === 'Organization') {
                    continue;
                }

                // Check if the model has an organization_id column
                if (! $this->modelHasOrganizationId($reflection)) {
                    continue;
                }

                $models[] = $className;

                $this->assertTrue(
                    in_array(BelongsToOrganization::class, class_uses_recursive($className), true),
                    sprintf(
                        'Model "%s" has an "organization_id" column but does not use the "%s" trait.',
                        $className,
                        BelongsToOrganization::class,
                    ),
                );
            }
        }

        if (empty($models)) {
            $this->markTestSkipped('No tenant models found to test.');
        }
    }

    private function getClassNameFromFile(string $file): ?string
    {
        $contents = (string) file_get_contents($file);

        if (preg_match('/^namespace\s+([^;]+);/m', $contents, $namespaceMatches) !== 1) {
            return null;
        }

        if (preg_match('/^class\s+(\w+)/m', $contents, $classMatches) !== 1) {
            return null;
        }

        return $namespaceMatches[1].'\\'.$classMatches[1];
    }

    private function modelHasOrganizationId(ReflectionClass $reflection): bool
    {
        if (! $reflection->hasProperty('fillable')) {
            return false;
        }

        $fillable = $reflection->getProperty('fillable')->getDefaultValue();

        if (is_array($fillable) && in_array('organization_id', $fillable, true)) {
            return true;
        }

        if (! $reflection->hasProperty('casts')) {
            return false;
        }

        $casts = $reflection->getProperty('casts')->getDefaultValue();

        return is_array($casts) && array_key_exists('organization_id', $casts);
    }
}
