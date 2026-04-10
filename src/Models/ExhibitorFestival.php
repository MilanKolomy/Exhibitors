<?php
declare(strict_types=1);

namespace App\Models;

use PDO;

class ExhibitorFestival
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function saveMany(int $exhibitorId, array $festivalIds): void
    {
        $stmt = $this->pdo->prepare("
            INSERT IGNORE INTO exhibitor_festivals (exhibitor_id, festival_id)
            VALUES (:exhibitor_id, :festival_id)
        ");

        foreach ($festivalIds as $festivalId) {
            $stmt->execute([
                ':exhibitor_id' => $exhibitorId,
                ':festival_id'  => (int) $festivalId,
            ]);
        }
    }
}