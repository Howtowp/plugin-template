<?php

declare(strict_types=1);

namespace Forouzandea\Bluwp\Database\Connection;

trait MonitorsQueries
{
    private array $queryListeners = [];
    private array $slowListeners = [];
    private array $fingerprints = [];
    private int $queryCount = 0;
    private float $queryTime = 0;
    private int $listenerFailures = 0;
    private bool $notifying = false;
    public function listen(callable $callback): void
    {
        $this->queryListeners[] = $callback;
    }
    public function whenQueryingForLongerThan(float $milliseconds, callable $callback): void
    {
        if ($milliseconds < 0) {
            throw new \InvalidArgumentException('Duration must be nonnegative.');
        }
        $this->slowListeners[] = ['threshold' => $milliseconds, 'callback' => $callback, 'fired' => false];
    }
    public function queryStats(): array
    {
        return ['count' => $this->queryCount, 'milliseconds' => $this->queryTime, 'fingerprints' => $this->fingerprints, 'listener_failures' => $this->listenerFailures];
    }
    public function resetQueryStats(): void
    {
        $this->queryCount = 0;
        $this->queryTime = 0;
        $this->fingerprints = [];
        $this->listenerFailures = 0;
        foreach ($this->slowListeners as &$listener) {
            $listener['fired'] = false;
        }
    }
    public function clearQueryListeners(): void
    {
        $this->queryListeners = $this->slowListeners = [];
    }
    private function recordQuery(string $sql, array $bindings, int $started, ?int $error, bool $notify = true): void
    {
        $duration = (hrtime(true) - $started) / 1000000;
        $fingerprint = hash('sha256', $sql);
        ++$this->queryCount;
        $this->queryTime += $duration;
        if (isset($this->fingerprints[$fingerprint]) || count($this->fingerprints) < 1000) {
            $this->fingerprints[$fingerprint] = ($this->fingerprints[$fingerprint] ?? 0) + 1;
        }
        if (!$notify || $this->notifying) {
            return;
        }
        // Neither raw SQL nor values are included: literals may contain secrets.
        $event = (object) ['fingerprint' => $fingerprint, 'time' => $duration, 'bindingCount' => count($bindings), 'errorCode' => $error];
        $callbacks = $this->queryListeners;
        foreach ($this->slowListeners as &$listener) {
            if (!$listener['fired'] && $this->queryTime > $listener['threshold']) {
                $listener['fired'] = true;
                $callbacks[] = $listener['callback'];
            }
        }
        unset($listener);
        $this->notifying = true;
        try {
            foreach ($callbacks as $callback) {
                try {
                    $callback($event);
                } catch (\Throwable $error) {
                    ++$this->listenerFailures;
                }
            }
        } finally {
            $this->notifying = false;
        }
    }
}
