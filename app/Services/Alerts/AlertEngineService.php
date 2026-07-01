<?php
declare(strict_types=1);

namespace App\Services\Alerts;

use App\Entities\Alert;
use App\Entities\ThreatIntelligence;
use App\Domain\Alert\AlertRepository;

class AlertEngineService
{
    private \PDO $db;
    private AlertRepository $alertRepo;

    private const IOC_FREQ_THRESHOLD = 5;

    public function __construct(\PDO $db, AlertRepository $alertRepo)
    {
        $this->db = $db;
        $this->alertRepo = $alertRepo;
    }

    public function processThreatIntelligence(ThreatIntelligence $threat): void
    {
        if (!$this->shouldAlertFromThreat($threat)) {
            return;
        }

        foreach ($threat->getAffectedAssets() as $asset) {
            $alert = new Alert();
            $alert->setType('correlated_threat');
            $alert->setSeverity($threat->getSeverity());
            $alert->setMessage(sprintf(
                "[%s] %s | Confidence %d%% | Assets: %s",
                $threat->getSeverity(),
                $threat->getTitle(),
                $threat->getConfidenceScore(),
                $asset['name'] ?? 'Unknown'
            ));
            $alert->setAssetId($asset['id'] ?? null);
            $alert->setThreatId($threat->getId());
            $alert->setStatus('open');

            $this->alertRepo->save($alert);
        }
    }

    public function processIoc(array $ioc): void
    {
        $isCritical = $this->iocHitsCriticalAsset($ioc);
        $freq = $ioc['frequency'] ?? 0;

        if (!$isCritical && $freq <= self::IOC_FREQ_THRESHOLD) {
            return;
        }

        $severity = 'Medium';
        if ($isCritical && $freq > self::IOC_FREQ_THRESHOLD) {
            $severity = 'Critical';
        } elseif ($isCritical) {
            $severity = 'High';
        }

        $alert = new Alert();
        $alert->setType('ioc_match');
        $alert->setSeverity($severity);
        $alert->setMessage(sprintf(
            "IOC %s '%s' matched %s (freq: %d)",
            $ioc['type'],
            $ioc['value'],
            $isCritical ? 'CRITICAL asset' : 'known pattern',
            $freq
        ));
        $alert->setIocId($ioc['id'] ?? null);
        $alert->setStatus('open');

        $this->alertRepo->save($alert);
    }

    public function processCve(array $cve): void
    {
        if (($cve['cvss_score'] ?? 0) < 8.0) {
            return;
        }

        $alert = new Alert();
        $alert->setType('cve_critical');
        $alert->setSeverity('High');
        $alert->setMessage(sprintf(
            "Critical CVE %s (CVSS %.1f) — %s",
            $cve['cve_id'],
            $cve['cvss_score'],
            substr($cve['description'] ?? '', 0, 200)
        ));
        $alert->setStatus('open');

        $this->alertRepo->save($alert);
    }

    private function shouldAlertFromThreat(ThreatIntelligence $threat): bool
    {
        return in_array($threat->getSeverity(), ['High', 'Critical'], true)
            || $threat->getConfidenceScore() >= 95;
    }

    private function iocHitsCriticalAsset(array $ioc): bool
    {
        $val = $ioc['value'];
        $type = strtolower($ioc['type'] ?? '');

        if ($type === 'ip') {
            $stmt = $this->db->prepare(
                "SELECT COUNT(*) FROM assets WHERE (ip = ? OR public_ip = ?) AND is_critical = 1"
            );
            $stmt->execute([$val, $val]);
        } elseif ($type === 'domain') {
            $stmt = $this->db->prepare(
                "SELECT COUNT(*) FROM assets WHERE (domain = ? OR hostname LIKE ?) AND is_critical = 1"
            );
            $stmt->execute([$val, "%{$val}%"]);
        } else {
            return false;
        }

        return (int)$stmt->fetchColumn() > 0;
    }
}