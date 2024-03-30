<?php

declare(strict_types=1);

require_once __DIR__.'/../vendor/autoload.php';

$datePrefix = (new DateTimeImmutable('now'))->format('Ym');
$prefix = 'DEPTRAC_'.$datePrefix;

// Path to the PHPStan PHAR
$phpstanPharPath = __DIR__.'/../vendor/phpstan/phpstan/phpstan.phar';

// Temporary directory for extraction and scoped files
$tempDir = __DIR__.'/deptrac-build/phpstan';

// Extract the PHAR
$phar = new Phar($phpstanPharPath);
$phar->extractTo($tempDir, null, true);

// Apply scoping to the extracted files
applyScoping($tempDir, $prefix);

// Repackage the scoped files into a new PHAR
repackagePhar($tempDir, __DIR__.'/deptrac-build/phpstan.phar');

// Clean up the temporary directory
cleanup($tempDir);

echo "Scoped PHPStan PHAR created successfully!\n";

// Function to apply scoping
function applyScoping(string $directory, string $prefix): void
{
    $finder = new Symfony\Component\Finder\Finder();
    $finder->files()->in($directory);

    foreach ($finder as $file) {
        $contents = file_get_contents($file->getRealPath());
        $scopedContents = preg_replace('/^namespace\s+(.+?);/m', 'namespace '.$prefix.'\\\\$1;', $contents);
        file_put_contents($file->getRealPath(), $scopedContents);
    }
}

// Function to repackage PHAR
function repackagePhar(string $directory, string $outputPhar): void
{
    $phar = new Phar($outputPhar);
    $phar->buildFromDirectory($directory);
    $phar->setStub($phar->createDefaultStub('bootstrap.php'));
}

// Function to clean up temporary directory
function cleanup(string $directory): void
{
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );

    foreach ($files as $file) {
        if ($file->isDir()) {
            rmdir($file->getRealPath());
        } else {
            unlink($file->getRealPath());
        }
    }

    rmdir($directory);
}
