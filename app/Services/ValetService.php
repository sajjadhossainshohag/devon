<?php

namespace App\Services;

use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class ValetService
{
    private ?string $sudoPassword = null;
    private ?string $valetPath = null;
    
    public function __construct()
    {
        $this->valetPath = $this->findValetPath();
    }
    
    /**
     * Find the valet executable path
     */
    private function findValetPath(): string
    {
        // Common valet installation paths
        $possiblePaths = [
            '/usr/local/bin/valet',
            '/opt/homebrew/bin/valet',
            '/usr/bin/valet',
            '~/.composer/vendor/bin/valet',
            '~/.config/composer/vendor/bin/valet',
        ];
        
        foreach ($possiblePaths as $path) {
            $expandedPath = $this->expandPath($path);
            if (file_exists($expandedPath) && is_executable($expandedPath)) {
                return $expandedPath;
            }
        }
        
        // Try to find valet using which command
        $process = new Process(['which', 'valet']);
        $process->setEnv($this->getEnvironmentVariables());
        $process->run();
        
        if ($process->isSuccessful()) {
            return trim($process->getOutput());
        }
        
        return 'valet';
    }
    
    /**
     * Expand ~ to home directory
     */
    private function expandPath(string $path): string
    {
        if (strpos($path, '~') === 0) {
            return str_replace('~', $_SERVER['HOME'] ?? getenv('HOME') ?: '/Users/' . get_current_user(), $path);
        }
        return $path;
    }
    
    /**
     * Get proper environment variables including PATH
     */
    private function getEnvironmentVariables(): array
    {
        $env = $_ENV;
        $home = $_SERVER['HOME'] ?? getenv('HOME') ?: '/Users/' . get_current_user();
        
        // Build comprehensive PATH
        $pathComponents = [
            '/usr/local/bin',
            '/opt/homebrew/bin',
            '/usr/bin',
            '/bin',
            '/usr/sbin',
            '/sbin',
            $home . '/.composer/vendor/bin',
            $home . '/.config/composer/vendor/bin',
        ];
        
        // Add existing PATH if available
        if (isset($env['PATH'])) {
            $pathComponents = array_merge(explode(':', $env['PATH']), $pathComponents);
        } elseif (isset($_SERVER['PATH'])) {
            $pathComponents = array_merge(explode(':', $_SERVER['PATH']), $pathComponents);
        }
        
        // Remove duplicates and empty values
        $pathComponents = array_unique(array_filter($pathComponents));
        
        $env['PATH'] = implode(':', $pathComponents);
        $env['HOME'] = $home;
        
        return $env;
    }
    
    /**
     * Store sudo password for the session
     */
    public function setSudoPassword(string $password): void
    {
        $this->sudoPassword = $password;
    }
    
    /**
     * Execute command with potential sudo requirements
     */
    private function executeCommand(array $command, ?string $workingDirectory = null, bool $requiresSudo = false): Process
    {
        $process = new Process($command, $workingDirectory);

        // Set proper environment variables
        $process->setEnv($this->getEnvironmentVariables());
        $process->setTimeout(120);
        $process->run();
        
        return $process;
    }
    
    /**
     * Check if a command requires sudo
     */
    private function requiresSudo(array $command): bool
    {
        $sudoCommands = ['secure', 'unsecure', 'install', 'restart', 'stop', 'start', 'which-php'];
        return isset($command[1]) && in_array($command[1], $sudoCommands);
    }

    public function getSites(): array
    {
        // valet links doesn't require sudo
        $process = $this->executeCommand(['valet', 'links']);

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        $output = $process->getOutput();
        $sites = [];
        
        foreach (explode("\n", trim($output)) as $line) {
            if (strpos($line, '|') !== false) {
                // Parse table format
                $parts = array_map('trim', explode('|', $line));
                if (count($parts) >= 3 && $parts[1] !== 'Site' && !empty($parts[1])) {
                    $sites[] = [
                        'name' => $parts[1],
                        'path' => $parts[2],
                        'url' => 'http://' . $parts[1] . '.test'
                    ];
                }
            } elseif (strpos($line, '-> ') !== false) {
                // Parse older format
                [$name, $path] = explode('->', $line, 2);
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
        $process = $this->executeCommand(['valet', 'link', $name], $path);
        return $process->isSuccessful();
    }

    public function unlinkSite(string $name): bool
    {
        $process = $this->executeCommand(['valet', 'unlink', $name]);
        return $process->isSuccessful();
    }

    public function secureSite(string $name): bool
    {
        $process = $this->executeCommand(['valet', 'secure', $name], null, true);
        return $process->isSuccessful();
    }

    public function unsecureSite(string $name): bool
    {
        $process = $this->executeCommand(['valet', 'unsecure', $name], null, true);
        return $process->isSuccessful();
    }

    public function restartValet(): bool
    {
        $process = $this->executeCommand(['valet', 'restart'], null, true);
        return $process->isSuccessful();
    }

    public function getValetStatus(): array
    {
        $process = $this->executeCommand(['valet', 'status'], null, true);
        
        return [
            'running' => $process->isSuccessful(),
            'output' => $process->getOutput(),
            'error' => $process->getErrorOutput()
        ];
    }

    public function getPhpVersion(): string
    {
        $process = $this->executeCommand(['valet', 'which-php'], null, true);
        return $process->isSuccessful() ? trim($process->getOutput()) : 'Unknown';
    }

    public function switchPhpVersion(string $version): bool
    {
        $process = $this->executeCommand(['valet', 'use', $version], null, true);
        return $process->isSuccessful();
    }
    
    public function hasSudoAccess(): bool
    {
        $process = new Process(['sudo', '-n', 'true']);
        $process->run();
        return $process->isSuccessful();
    }
    
    public function validateSudoPassword(string $password): bool
    {
        $process = Process::fromShellCommandline(
            sprintf('echo %s | sudo -S true 2>/dev/null', escapeshellarg($password))
        );
        $process->run();
        
        if ($process->isSuccessful()) {
            $this->setSudoPassword($password);
            return true;
        }
        
        return false;
    }
    
    public function clearSudoPassword(): void
    {
        $this->sudoPassword = null;
    }
    
    public function getValetInfo(): array
    {
        return [
            'path' => $this->valetPath,
            'exists' => file_exists($this->valetPath),
            'executable' => is_executable($this->valetPath),
            'version' => $this->getValetVersion(),
        ];
    }
    
    public function getValetVersion(): string
    {
        try {
            // valet --version doesn't require sudo
            $process = $this->executeCommand(['valet', '--version']);
            return $process->isSuccessful() ? trim($process->getOutput()) : 'Unknown';
        } catch (\Exception $e) {
            return 'Error: ' . $e->getMessage();
        }
    }
    
    public function testValetInstallation(): array
    {
        $info = $this->getValetInfo();
        $info['test_command'] = false;
        
        try {
            $process = $this->executeCommand(['valet', '--version']);
            $info['test_command'] = true;
            $info['test_output'] = $process->getOutput();
            $info['test_success'] = $process->isSuccessful();
        } catch (\Exception $e) {
            $info['test_error'] = $e->getMessage();
        }
        
        return $info;
    }
    
    public function getParkedDirectories(): array
    {
        try {
            // valet paths doesn't require sudo
            $process = $this->executeCommand(['valet', 'paths']);
            
            if (!$process->isSuccessful()) {
                return [];
            }
            
            $output = trim($process->getOutput());
            $paths = [];
            
            foreach (explode("\n", $output) as $line) {
                $line = trim($line);
                if ($line && $line !== 'Valet will also serve for sites in the following directories:') {
                    $paths[] = $line;
                }
            }
            
            return $paths;
        } catch (\Exception $e) {
            return [];
        }
    }
    
    public function parkDirectory(string $path): bool
    {
        $process = $this->executeCommand(['valet', 'park'], $path);
        return $process->isSuccessful();
    }
    
    public function unparkDirectory(string $path): bool
    {
        $process = $this->executeCommand(['valet', 'forget'], $path);
        return $process->isSuccessful();
    }
    
    public function getValetLogs(): array
    {
        try {
            $process = $this->executeCommand(['valet', 'log'], null, true);
            
            return [
                'success' => $process->isSuccessful(),
                'output' => $process->getOutput(),
                'error' => $process->getErrorOutput()
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    public function startValet(): bool
    {
        $process = $this->executeCommand(['valet', 'start'], null, true);
        return $process->isSuccessful();
    }
    
    public function stopValet(): bool
    {
        $process = $this->executeCommand(['valet', 'stop'], null, true);
        return $process->isSuccessful();
    }
    
    public function diagnoseValet(): array
    {
        $diagnosis = [];
        
        // Test 1: File existence and permissions
        $diagnosis['file_check'] = [
            'path' => $this->valetPath,
            'exists' => file_exists($this->valetPath),
            'readable' => is_readable($this->valetPath),
            'executable' => is_executable($this->valetPath),
        ];
        
        // Test 2: Basic execution (no sudo needed)
        $diagnosis['basic_execution'] = $this->testRawCommand($this->valetPath . ' --version', false);
        
        // Test 3: Environment
        $env = $this->getEnvironmentVariables();
        $diagnosis['environment'] = [
            'PATH' => $env['PATH'],
            'HOME' => $env['HOME'],
            'USER' => get_current_user(),
        ];
        
        // Test 4: Valet configuration
        $configPath = $env['HOME'] . '/.config/valet';
        $diagnosis['valet_config'] = [
            'config_dir_exists' => is_dir($configPath),
            'config_dir_readable' => is_readable($configPath),
        ];
        
        return $diagnosis;
    }
    
    /**
     * Test raw command execution for debugging
     */
    public function testRawCommand(string $command, bool $requiresSudo = false): array
    {
        try {
            $process = Process::fromShellCommandline($command);
            
            if ($requiresSudo && $this->sudoPassword) {
                $process->setInput($this->sudoPassword . "\n");
            }
            
            $process->setEnv($this->getEnvironmentVariables());
            $process->setTimeout(30);
            $process->run();
            
            return [
                'command' => $command,
                'success' => $process->isSuccessful(),
                'exit_code' => $process->getExitCode(),
                'output' => $process->getOutput(),
                'error' => $process->getErrorOutput(),
                'working_dir' => $process->getWorkingDirectory(),
            ];
        } catch (\Exception $e) {
            return [
                'command' => $command,
                'success' => false,
                'error' => $e->getMessage(),
                'exception' => get_class($e),
            ];
        }
    }
    
    public function fixValetIssues(string $password = null): array
    {
        $results = [];
        
        if ($password) {
            $this->setSudoPassword($password);
        }
        
        // Step 1: Try valet install
        $results['install_attempt'] = $this->attemptValetInstall();
        
        // Step 2: Try to start services
        $results['service_start'] = $this->attemptStartServices();
        
        // Step 3: Test again
        $results['post_fix_test'] = $this->testValetInstallation();
        
        return $results;
    }
    
    private function attemptValetInstall(): array
    {
        try {
            $process = $this->executeCommand(['valet', 'install'], null, true);
            return [
                'success' => $process->isSuccessful(),
                'output' => $process->getOutput(),
                'error' => $process->getErrorOutput(),
                'exit_code' => $process->getExitCode(),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
    
    private function attemptStartServices(): array
    {
        try {
            $process = $this->executeCommand(['valet', 'start'], null, true);
            return [
                'success' => $process->isSuccessful(),
                'output' => $process->getOutput(),
                'error' => $process->getErrorOutput(),
                'exit_code' => $process->getExitCode(),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}
