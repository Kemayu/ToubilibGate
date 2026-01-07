<?php
declare(strict_types=1);

namespace toubilib\infra\repositories;

use Psr\Log\LoggerInterface;
use toubilib\core\application\ports\spi\repositoryInterfaces\RdvRepositoryInterface;

class PDORdvRepository implements RdvRepositoryInterface
{
    private \PDO $pdoRdv;
    private \PDO $pdoPrat;
    private \PDO $pdoPat;
    private ?LoggerInterface $logger;

    public function __construct(
        \PDO $pdoRdv,
        \PDO $pdoPrat,
        \PDO $pdoPat,
        ?LoggerInterface $logger = null
    )
    {
        $this->pdoRdv = $pdoRdv;
        $this->pdoPrat = $pdoPrat;
        $this->pdoPat = $pdoPat;
        $this->logger = $logger;
        $this->pdoRdv->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
    }

    public function findCreneauxPraticien(string $praticienId, string $from, string $to): array
    {
        if ($this->logger) {
            $this->logger->debug('[PDORdvRepository] findCreneauxPraticien', [
                'praticien_id' => $praticienId,
                'from' => $from,
                'to' => $to
            ]);
        }
        //status 1 rdv annulé
        $sql = '
            SELECT * FROM rdv 
            WHERE praticien_id = :praticien_id
              AND date_heure_debut >= :from
              AND date_heure_debut < :to
              AND (status IS NULL OR status <> 1)
            ORDER BY date_heure_debut ASC
        ';

        $stmt = $this->pdoRdv->prepare($sql);
        $stmt->execute([
            ':praticien_id' => $praticienId,
            ':from' => $from,
            ':to' => $to,
        ]);

        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        if ($this->logger) {
            $this->logger->debug('PDORdvRepository: fetched creneaux', ['count' => count($rows), 'praticien' => $praticienId, 'from' => $from, 'to' => $to]);
        }

        return $rows ?: [];
    }

    public function findById(string $id): ?array
    {
        $sql = 'SELECT id, praticien_id, patient_id, patient_email, date_heure_debut, date_heure_fin, duree, status, motif_visite, date_creation
                FROM rdv
                WHERE id = :id
                LIMIT 1';

        $stmt = $this->pdoRdv->prepare($sql);
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        if ($this->logger) {
            $this->logger->debug('PDORdvRepository: fetched rdv by id', ['id' => $id, 'found' => (bool)$row]);
        }

        return $row ?: null;
    }

    public function getRendezVousById(string $id): ?array
    {
        // Alias de findById pour AuthzService
        return $this->findById($id);
    }

    public function saveRendezVous(array $data): ?string
    {
        $sql = 'INSERT INTO rdv
            (id, praticien_id, patient_id, date_heure_debut, date_heure_fin, duree, motif_visite, date_creation, patient_email)
            VALUES (:id, :praticien_id, :patient_id, :date_heure_debut, :date_heure_fin, :duree, :motif_visite, :date_creation, :patient_email)';

        $stmt = $this->pdoRdv->prepare($sql);

        $params = [
            ':id' => $data['id'],
            ':praticien_id' => $data['praticien_id'],
            ':patient_id' => $data['patient_id'],
            ':date_heure_debut' => $data['date_heure_debut'],
            ':date_heure_fin' => $data['date_heure_fin'] ?? null,
            ':duree' => $data['duree'],
            ':motif_visite' => $data['motif_visite'] ?? null,
            ':date_creation' => $data['date_creation'] ?? (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
            ':patient_email' => $data['patient_email'] ?? null,
        ];

        try {
            $ok = $stmt->execute($params);
            if ($this->logger) {
                $this->logger->debug('PDORdvRepository: saved rdv', ['id' => $data['id'], 'ok' => $ok]);
            }
            return $ok ? $data['id'] : null;
        } catch (\Throwable $e) {
            if ($this->logger) {
                $this->logger->error('PDORdvRepository: save failed', ['error' => $e->getMessage()]);
            }
            return null;
        }
    }

    public function existsPraticienById(string $praticienId): bool
    {
        $praticienId = trim((string)$praticienId);
        if ($praticienId === '') {
            return false;
        }
        $sql = 'SELECT 1 FROM praticien WHERE id = :id LIMIT 1';
        $stmt = $this->pdoPrat->prepare($sql);
        $stmt->execute(['id' => $praticienId]);
        return (bool)$stmt->fetchColumn();
    }

    public function existsPatientById(string $patientId): bool
    {
        $patientId = trim((string)$patientId);
        if ($patientId === '') {
            return false;
        }
        $tables = ['patient', 'patients', 'dossiers', 'dossier'];
        foreach ($tables as $t) {
            try {
                $sql = "SELECT 1 FROM {$t} WHERE id = :id LIMIT 1";
                $stmt = $this->pdoPat->prepare($sql);
                $stmt->execute(['id' => $patientId]);
                if ($stmt->fetchColumn()) {
                    return true;
                }
            } catch (\PDOException $e) {
                continue;
            }
        }
        return false;
    }

    public function getMotifsForPraticien(string $praticienId): array
    {
        try {
            $sql = '
                SELECT mv.id AS motif_id, mv.libelle
                FROM praticien2motif p2m
                JOIN motif_visite mv ON mv.id = p2m.motif_id
                WHERE p2m.praticien_id = :pid
            ';
            $stmt = $this->pdoPrat->prepare($sql);
            $stmt->execute(['pid' => $praticienId]);
            $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            $out = [];
            foreach ($rows as $r) {
                $out[] = [
                    'id' => (string)($r['motif_id'] ?? ''),
                    'libelle' => (string)($r['libelle'] ?? '')
                ];
            }
            return $out;
        } catch (\PDOException $e) {
            if ($this->logger) {
                $this->logger->warning('getMotifsForPraticien failed', ['praticienId' => $praticienId, 'err' => $e->getMessage()]);
            }
            return [];
        }
    }

    public function updateRendezVous(string $id, array $data): bool
    {
        if (empty($id) || empty($data)) {
            return false;
        }

        $sets = [];
        $params = [':id' => $id];
        foreach ($data as $k => $v) {
            // protection basique du nom de colonne
            $col = preg_replace('/[^a-z0-9_]/i', '', $k);
            $sets[] = "{$col} = :{$col}";
            $params[":{$col}"] = $v;
        }

        $sql = 'UPDATE rdv SET ' . implode(', ', $sets) . ' WHERE id = :id';
        $stmt = $this->pdoRdv->prepare($sql);
        try {
            $ok = $stmt->execute($params);
            if ($this->logger) {
                $this->logger->debug('PDORdvRepository: updated rdv', ['id' => $id, 'ok' => $ok, 'data' => $data]);
            }
            return (bool)$ok;
        } catch (\Throwable $e) {
            if ($this->logger) {
                $this->logger->error('PDORdvRepository: update failed', ['id' => $id, 'err' => $e->getMessage()]);
            }
            return false;
        }
    }

    public function updateStatus(string $id, int $status): bool
    {
        $sql = 'UPDATE rdv SET status = :status WHERE id = :id';
        try {
            $stmt = $this->pdoRdv->prepare($sql);
            $stmt->execute(['id' => $id, 'status' => $status]);
            return $stmt->rowCount() > 0;
        } catch (\Throwable $e) {
            if ($this->logger) {
                $this->logger->error('PDORdvRepository: updateStatus failed', ['id' => $id, 'status' => $status, 'err' => $e->getMessage()]);
            }
            return false;
        }
    }

    public function getRendezVousByPatientId(string $patientId): array
    {
        if ($this->logger) {
            $this->logger->debug('[PDORdvRepository] getRendezVousByPatientId', ['patient_id' => $patientId]);
        }

        // Récupérer les RDV du patient
        $sql = '
            SELECT id, praticien_id, patient_id, patient_email, date_heure_debut, 
                   date_heure_fin, duree, status, motif_visite, date_creation
            FROM rdv
            WHERE patient_id = :patient_id
            ORDER BY date_heure_debut DESC
        ';

        try {
            $stmt = $this->pdoRdv->prepare($sql);
            $stmt->execute([':patient_id' => $patientId]);
            $rdvs = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            if ($this->logger) {
                $this->logger->debug('PDORdvRepository: fetched rdvs for patient', ['count' => count($rdvs), 'patient_id' => $patientId]);
            }

            // Enrichir avec les infos praticien
            $result = [];
            foreach ($rdvs as $rdv) {
                $praticienId = $rdv['praticien_id'];
                
                // Récupérer les infos du praticien
                $sqlPrat = 'SELECT id, nom, prenom, specialite_id FROM praticien WHERE id = :id LIMIT 1';
                $stmtPrat = $this->pdoPrat->prepare($sqlPrat);
                $stmtPrat->execute([':id' => $praticienId]);
                $praticien = $stmtPrat->fetch(\PDO::FETCH_ASSOC);

                if ($praticien) {
                    // Récupérer le libellé de la spécialité
                    $sqlSpec = 'SELECT libelle FROM specialite WHERE id = :id LIMIT 1';
                    $stmtSpec = $this->pdoPrat->prepare($sqlSpec);
                    $stmtSpec->execute([':id' => $praticien['specialite_id']]);
                    $specialite = $stmtSpec->fetchColumn();

                    $result[] = [
                        'id' => $rdv['id'],
                        'date_heure_debut' => $rdv['date_heure_debut'],
                        'date_heure_fin' => $rdv['date_heure_fin'],
                        'duree' => (int)$rdv['duree'],
                        'status' => (int)$rdv['status'],
                        'motif_visite' => $rdv['motif_visite'],
                        'date_creation' => $rdv['date_creation'],
                        'praticien_id' => $praticien['id'],
                        'praticien_nom' => $praticien['nom'],
                        'praticien_prenom' => $praticien['prenom'],
                        'praticien_specialite' => $specialite ?: null,
                    ];
                }
            }

            return $result;
        } catch (\Throwable $e) {
            if ($this->logger) {
                $this->logger->error('PDORdvRepository: getRendezVousByPatientId failed', ['patient_id' => $patientId, 'err' => $e->getMessage()]);
            }
            return [];
        }
    }

    public function isPraticienIndisponible(string $praticienId, \DateTimeImmutable $debut, \DateTimeImmutable $fin): bool
    {
        if ($this->logger) {
            $this->logger->debug('[PDORdvRepository] isPraticienIndisponible', [
                'praticien_id' => $praticienId,
                'debut' => $debut->format('Y-m-d H:i:s'),
                'fin' => $fin->format('Y-m-d H:i:s')
            ]);
        }

        // Vérifier les congés dans la table indisponibilite (si elle existe)
        try {
            $sql = '
                SELECT COUNT(*) as count
                FROM indisponibilite
                WHERE praticien_id = :praticien_id
                  AND (
                    (date_debut <= :debut AND date_fin >= :debut)
                    OR (date_debut <= :fin AND date_fin >= :fin)
                    OR (date_debut >= :debut AND date_fin <= :fin)
                  )
            ';
            
            $stmt = $this->pdoPrat->prepare($sql);
            $stmt->execute([
                ':praticien_id' => $praticienId,
                ':debut' => $debut->format('Y-m-d'),
                ':fin' => $fin->format('Y-m-d'),
            ]);
            
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);
            $count = (int)($result['count'] ?? 0);

            if ($count > 0) {
                if ($this->logger) {
                    $this->logger->debug('PDORdvRepository: praticien indisponible (congés)', ['count' => $count]);
                }
                return true;
            }
        } catch (\PDOException $e) {
            // Si la table n'existe pas, on ignore l'erreur
            if ($this->logger) {
                $this->logger->warning('PDORdvRepository: table indisponibilite inexistante', ['err' => $e->getMessage()]);
            }
        }

        return false;
    }
}
