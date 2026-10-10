<?php
function dreamGridSessionSecret(?string $secretDirectory = null): string {
    $path = ($secretDirectory ?? __DIR__) . '/session_secret.php';
    if (!is_file($path)) {
        $directory = __DIR__ . '/../private/login-throttle';
        if (!is_dir($directory) && !@mkdir($directory, 0700, true) && !is_dir($directory)) {
            throw new RuntimeException('Authentication temporarily unavailable.');
        }
        // Serialize first-use creation without replacing any existing secret.
        $lock = @fopen($directory . '/secret.lock', 'c+b');
        if (!$lock) throw new RuntimeException('Authentication temporarily unavailable.');
        try {
            $deadline = microtime(true) + 1;
            while (!flock($lock, LOCK_EX | LOCK_NB)) {
                if (microtime(true) >= $deadline) throw new RuntimeException('Authentication temporarily unavailable.');
                usleep(10000);
            }
            if (!is_file($path)) {
                $data = "<?php\nreturn " . var_export(bin2hex(random_bytes(32)), true) . ";\n";
                $temporary = $directory . '/session-secret-' . bin2hex(random_bytes(12)) . '.tmp';
                try {
                    if (file_put_contents($temporary, $data) !== strlen($data) || !rename($temporary, $path)) {
                        throw new RuntimeException('Authentication temporarily unavailable.');
                    }
                } finally { if (is_file($temporary)) @unlink($temporary); }
            }
        } finally { flock($lock, LOCK_UN); fclose($lock); }
    }
    $secret = include $path;
    if (!is_string($secret) || $secret === '') throw new RuntimeException('Authentication temporarily unavailable.');
    return $secret;
}
