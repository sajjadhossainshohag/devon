<?php

namespace App\Services;

use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class ValetService
{
    public function getSites(): array
    {
        $process = new Process(['valet', 'links']);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        $output = $process->getOutput();
        $sites = [];
        
        foreach (explode("\n", trim($output)) as $line) {
            if (strpos($line, '-> ') !== false) {
                [$name, $path] = explode(' -> ', $line, 2);
                $sites[] = [
                    'name' => trim($name),
                    'path' => trim($path),
                    'url' => 'http://' . trim($name) . '.test'
                ];
            }
        }

        return $sites;
    }

    public function linkSite(string $name, string $path): bool
    {
        $process = new Process(['valet', 'link', $name], $path);
        $process->run();

        return $process->isSuccessful();
    }

    public function unlinkSite(string $name): bool
    {
        $process = new Process(['valet', 'unlink', $name]);
        $process->run();

        return $process->isSuccessful();
    }

    public function secureSite(string $name): bool
    {
        $process = new Process(['valet', 'secure', $name]);
        $process->run();

        return $process->isSuccessful();
    }

    public function unsecureSite(string $name): bool
    {
        $process = new Process(['valet', 'unsecure', $name]);
        $process->run();

        return $process->isSuccessful();
    }

    public function restartValet(): bool
    {
        $process = new Process(['valet', 'restart']);
        $process->run();

        return $process->isSuccessful();
    }

    public function getValetStatus(): array
    {
        $process = new Process(['valet', 'status']);
        $process->run();

        return [
            'running' => $process->isSuccessful(),
            'output' => $process->getOutput()
        ];
    }

    public function getPhpVersion(): string
    {
        $process = new Process(['valet', 'which-php']);
        $process->run();

        return $process->isSuccessful() ? trim($process->getOutput()) : 'Unknown';
    }

    public function switchPhpVersion(string $version): bool
    {
        $process = new Process(['valet', 'use', $version]);
        $process->run();

        return $process->isSuccessful();
    }
}
