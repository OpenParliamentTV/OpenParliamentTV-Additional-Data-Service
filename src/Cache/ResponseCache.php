<?php

class ResponseCache
{
    private ?PDO $db = null;

    public function __construct(string $dbPath)
    {
        try {
            $dir = dirname($dbPath);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }

            $this->db = new PDO('sqlite:' . $dbPath);
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->db->exec('PRAGMA journal_mode=WAL');
            $this->db->exec('CREATE TABLE IF NOT EXISTS cache (
                key        TEXT    PRIMARY KEY,
                response   TEXT    NOT NULL,
                expires_at INTEGER NOT NULL,
                created_at INTEGER NOT NULL
            )');
        } catch (Exception $e) {
            error_log('ResponseCache: failed to initialise SQLite cache — ' . $e->getMessage());
            $this->db = null;
        }
    }

    /**
     * Return a cached response, or null on miss / if the entry is expired.
     * Expired entries are kept in the DB for stale-while-error fallback.
     */
    public function get(string $key): ?array
    {
        if ($this->db === null) {
            return null;
        }

        $stmt = $this->db->prepare('SELECT response, expires_at FROM cache WHERE key = ?');
        $stmt->execute([$key]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        if ($row['expires_at'] < time()) {
            return null;
        }

        return json_decode($row['response'], true);
    }

    /**
     * Return a cached response regardless of expiry.
     * Used as a fallback when an upstream API returns an error.
     */
    public function getStale(string $key): ?array
    {
        if ($this->db === null) {
            return null;
        }

        $stmt = $this->db->prepare('SELECT response FROM cache WHERE key = ?');
        $stmt->execute([$key]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? json_decode($row['response'], true) : null;
    }

    /**
     * Store a response. TTL of 0 means the entry never expires.
     */
    public function set(string $key, array $response, int $ttl): void
    {
        if ($this->db === null) {
            return;
        }

        $expiresAt = ($ttl === 0) ? PHP_INT_MAX : time() + $ttl;

        $stmt = $this->db->prepare(
            'INSERT OR REPLACE INTO cache (key, response, expires_at, created_at) VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([$key, json_encode($response), $expiresAt, time()]);

        // Lazy purge: ~1% of writes to prevent unbounded DB growth
        if (mt_rand(1, 100) === 1) {
            $this->purgeExpired();
        }
    }

    /**
     * Delete all expired entries (never-expire entries with PHP_INT_MAX are kept).
     */
    private function purgeExpired(): void
    {
        $this->db->prepare(
            'DELETE FROM cache WHERE expires_at < ?'
        )->execute([time()]);
    }

    /**
     * Build a deterministic cache key from an array of request parameters.
     */
    public static function makeKey(array $params): string
    {
        ksort($params);
        return md5(json_encode($params));
    }
}
