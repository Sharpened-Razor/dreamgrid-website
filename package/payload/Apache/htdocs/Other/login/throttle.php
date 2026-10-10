<?php
/* Website-only throttle. Never uses forwarding headers or changes account storage.
 * Reserve attempts under a short exclusive lock before authentication so parallel
 * requests cannot all pass the same counter. Rejected attempts never extend expiry. */
function dreamGridThrottleState(string $file, callable $operation) {
    $directory = dirname($file);
    if (!is_dir($directory) && !@mkdir($directory, 0700, true) && !is_dir($directory)) {
        throw new RuntimeException('Authentication temporarily unavailable.');
    }
    $handle = @fopen($file, 'c+b');
    if (!$handle) throw new RuntimeException('Authentication temporarily unavailable.');
    try {
        // Bounded lock acquisition; never occupy a PHP worker indefinitely.
        $deadline = microtime(true) + 1;
        while (!flock($handle, LOCK_EX | LOCK_NB)) {
            if (microtime(true) >= $deadline) throw new RuntimeException('Authentication temporarily unavailable.');
            usleep(10000);
        }
        if (fstat($handle)['size'] > 2097152) throw new RuntimeException('Authentication temporarily unavailable.');
        $raw = stream_get_contents($handle);
        $state = $raw === '' ? ['sources' => [], 'pairs' => []] : json_decode($raw, true);
        if (!is_array($state) || !is_array($state['sources'] ?? null) || !is_array($state['pairs'] ?? null)) {
            throw new RuntimeException('Authentication temporarily unavailable.');
        }
        $result = $operation($state);
        $encoded = json_encode($state, JSON_THROW_ON_ERROR);
        rewind($handle);
        if (fwrite($handle, $encoded) !== strlen($encoded) || !ftruncate($handle, strlen($encoded)) || !fflush($handle)) {
            throw new RuntimeException('Authentication temporarily unavailable.');
        }
        return $result;
    } finally {
        flock($handle, LOCK_UN);
        fclose($handle);
    }
}
function dreamGridThrottleKeys(string $avatar, string $source, string $secret): array {
    if (filter_var($source, FILTER_VALIDATE_IP) === false) $source = 'unknown-source';
    else $source = bin2hex(inet_pton($source)); // Canonical IPv6 spelling.
    $avatar = preg_replace('/\s+/', ' ', trim($avatar));
    $avatar = function_exists('mb_strtolower') ? mb_strtolower($avatar, 'UTF-8') : strtolower($avatar);
    return [hash_hmac('sha256', 'source:'.$source, $secret),
        hash_hmac('sha256', 'pair:'.$source.'\0'.$avatar, $secret)];
}
function dreamGridThrottleBegin(string $file, array $keys, int $now): int {
    return dreamGridThrottleState($file, function (&$state) use ($keys, $now) {
        foreach (['sources' => 1024, 'pairs' => 3072] as $group => $limit) {
            foreach ($state[$group] as $key => $record) {
                if (!is_array($record) || !is_int($record['last'] ?? null) || !is_int($record['count'] ?? null) ||
                    !is_int($record['until'] ?? null) || $record['last'] + 300 <= $now) unset($state[$group][$key]);
            }
            if (count($state[$group]) > $limit) throw new RuntimeException('Authentication temporarily unavailable.');
        }
        $wait = max(0, ($state['sources'][$keys[0]]['until'] ?? 0) - $now,
            ($state['pairs'][$keys[1]]['until'] ?? 0) - $now);
        if ($wait > 0) return min(60, $wait);
        foreach (['sources' => [0, 1024, 10], 'pairs' => [1, 3072, 3]] as $group => $policy) {
            [$index, $limit, $threshold] = $policy;
            $key = $keys[$index];
            if (!isset($state[$group][$key]) && count($state[$group]) >= $limit) {
                uasort($state[$group], function ($a, $b) { return $a['last'] <=> $b['last']; });
                unset($state[$group][array_key_first($state[$group])]);
            }
            $count = min(32, ($state[$group][$key]['count'] ?? 0) + 1);
            $delay = $count < $threshold ? 0 : min(60, 2 ** min(6, $count - $threshold));
            $state[$group][$key] = ['count' => $count, 'last' => $now, 'until' => $now + $delay];
        }
        return 0;
    });
}
function dreamGridThrottleSuccess(string $file, array $keys): void {
    dreamGridThrottleState($file, function (&$state) use ($keys) {
        unset($state['pairs'][$keys[1]]);
        if (isset($state['sources'][$keys[0]])) {
            $record = &$state['sources'][$keys[0]];
            $record['count'] = max(0, $record['count'] - 1);
            if ($record['count'] < 10) $record['until'] = 0;
        }
        return null;
    });
}
