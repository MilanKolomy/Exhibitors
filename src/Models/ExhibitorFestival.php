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

     public function saveMany(int $exhibitorId, array $festivalsData): void
     {
          $stmt = $this->pdo->prepare("
        INSERT IGNORE INTO exhibitor_festivals
            (exhibitor_id, festival_id, space, electricity, price_space, price_elec, price_total)
        VALUES
            (:exhibitor_id, :festival_id, :space, :electricity, :price_space, :price_elec, :price_total)
    ");

          foreach ($festivalsData as $festivalId => $data) {
               $stmt->execute([
                    ':exhibitor_id' => $exhibitorId,
                    ':festival_id'  => (int) $festivalId,
                    ':space'        => $data['space']        ?? null,
                    ':electricity'  => $data['electricity']  ?? null,
                    ':price_space'  => (int)($data['priceSpace']  ?? 0),
                    ':price_elec'   => (int)($data['priceElec']   ?? 0),
                    ':price_total'  => (int)($data['priceTotal']  ?? 0),
               ]);
          }
     }
}
