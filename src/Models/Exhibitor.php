<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

class Exhibitor
{
     private PDO $pdo;

     public function __construct(PDO $pdo)
     {
          $this->pdo = $pdo;
     }

     public function create(array $data): int
     {
          $sql = "
    INSERT INTO exhibitors
        (ico, company, address, dic, contact_name, email, phone,
         website, social_networks, sortiment, terms_agreed, ip_address,
         total_price)
    VALUES
        (:ico, :company, :address, :dic, :contact_name, :email, :phone,
         :website, :social_networks, :sortiment, :terms_agreed, :ip_address,
         :total_price)
";

          $stmt = $this->pdo->prepare($sql);
          $stmt->execute([
               ':ico'             => $data['ico']             ?? null,
               ':company'         => $data['company'],
               ':address'         => $data['address'],
               ':dic'             => $data['dic']             ?? null,
               ':contact_name'    => $data['contact_name'],
               ':email'           => $data['email'],
               ':phone'           => $data['phone'],
               ':website'         => $data['website']         ?? null,
               ':social_networks' => $data['social_networks'] ?? null,
               ':sortiment'       => $data['sortiment'],
               ':terms_agreed'    => 1,
               ':ip_address'      => $data['ip_address']      ?? null,
               ':total_price'     => $data['total_price']     ?? 0,  // ← přidat
          ]);

          return (int) $this->pdo->lastInsertId();
     }

     public function getAll(?int $festivalId = null): array
     {
          if ($festivalId) {
               $sql = "
            SELECT e.*,
                GROUP_CONCAT(ef.festival_id   ORDER BY ef.festival_id) AS festival_ids,
                GROUP_CONCAT(ef.space         ORDER BY ef.festival_id) AS spaces,
                GROUP_CONCAT(ef.electricity   ORDER BY ef.festival_id) AS electricities,
                GROUP_CONCAT(ef.price_space   ORDER BY ef.festival_id) AS prices_space,
                GROUP_CONCAT(ef.price_elec    ORDER BY ef.festival_id) AS prices_elec,
                GROUP_CONCAT(ef.price_total   ORDER BY ef.festival_id) AS prices_total
            FROM exhibitors e
            JOIN exhibitor_festivals ef ON ef.exhibitor_id = e.id
            WHERE ef.festival_id = :festival_id
            GROUP BY e.id
            ORDER BY e.created_at DESC
        ";
               $stmt = $this->pdo->prepare($sql);
               $stmt->execute([':festival_id' => $festivalId]);
          } else {
               $sql = "
            SELECT e.*,
                GROUP_CONCAT(ef.festival_id   ORDER BY ef.festival_id) AS festival_ids,
                GROUP_CONCAT(ef.space         ORDER BY ef.festival_id) AS spaces,
                GROUP_CONCAT(ef.electricity   ORDER BY ef.festival_id) AS electricities,
                GROUP_CONCAT(ef.price_space   ORDER BY ef.festival_id) AS prices_space,
                GROUP_CONCAT(ef.price_elec    ORDER BY ef.festival_id) AS prices_elec,
                GROUP_CONCAT(ef.price_total   ORDER BY ef.festival_id) AS prices_total
            FROM exhibitors e
            LEFT JOIN exhibitor_festivals ef ON ef.exhibitor_id = e.id
            GROUP BY e.id
            ORDER BY e.created_at DESC
        ";
               $stmt = $this->pdo->query($sql);
          }

          return $stmt->fetchAll();
     }
}
