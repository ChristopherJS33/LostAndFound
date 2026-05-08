<?php
declare(strict_types=1);

class MatcherService
{
    private PDO $pdo;
    private array $config;

    public function __construct(PDO $pdo, array $config)
    {
        $this->pdo = $pdo;
        $this->config = $config;
    }

    public function updateMatchesForItem(int $itemId): void
    {
        if (!($this->config['app']['java_enabled'] ?? false)) {
            return;
        }

        $stmt = $this->pdo->prepare('SELECT * FROM items WHERE id = ?');
        $stmt->execute([$itemId]);
        $sourceItem = $stmt->fetch();
        if (!$sourceItem) {
            return;
        }

        $oppositeStatus = $sourceItem['status'] === 'lost' ? 'found' : 'lost';
        $candidatesStmt = $this->pdo->prepare('SELECT * FROM items WHERE status = ?');
        $candidatesStmt->execute([$oppositeStatus]);
        $candidates = $candidatesStmt->fetchAll();

        if (!$candidates) {
            return;
        }

        $payload = json_encode([
            'source' => $sourceItem,
            'candidates' => $candidates,
        ], JSON_UNESCAPED_SLASHES);

        $javaBin = $this->config['app']['java_bin'];
        $classpath = $this->config['app']['java_classpath'];
        $mainClass = $this->config['app']['java_main_class'];
        $command = escapeshellcmd($javaBin)
            . ' -cp ' . escapeshellarg($classpath)
            . ' ' . escapeshellarg($mainClass);

        $descriptorSpec = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = proc_open($command, $descriptorSpec, $pipes);
        if (!is_resource($process)) {
            return;
        }

        fwrite($pipes[0], $payload);
        fclose($pipes[0]);

        $stdout = stream_get_contents($pipes[1]);
        fclose($pipes[1]);

        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[2]);

        $exitCode = proc_close($process);
        if ($exitCode !== 0 || !$stdout) {
            error_log('Java matcher failed: ' . $stderr);
            return;
        }

        $matches = json_decode($stdout, true);
        if (!is_array($matches)) {
            return;
        }

        $deleteStmt = $this->pdo->prepare('DELETE FROM item_matches WHERE source_item_id = ?');
        $deleteStmt->execute([$itemId]);

        $insertStmt = $this->pdo->prepare(
            'INSERT INTO item_matches (source_item_id, matched_item_id, score) VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE score = VALUES(score)'
        );

        foreach ($matches as $match) {
            if (!isset($match['id'], $match['score'])) {
                continue;
            }
            $insertStmt->execute([
                $itemId,
                (int) $match['id'],
                round((float) $match['score'], 2),
            ]);
        }
    }
}
