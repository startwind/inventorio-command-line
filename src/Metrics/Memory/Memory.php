<?php

namespace Startwind\Inventorio\Metrics\Memory;
/**
 * @property array $data
 */
class Memory
{
    private const MEMORY_SIZE = 12; // one hour if every 5 minutes taken

    private static ?Memory $instance = null;

    private array $collection = [];

    private array $data = [];

    static public function getInstance(): Memory
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    private function __construct()
    {
    }

    public function addData(string $key, float $value): void
    {
        if (!array_key_exists($key, $this->data)) {
            $this->data[$key] = [];
        }

        if (count($this->data[$key]) >= self::MEMORY_SIZE) {
            array_shift($this->data[$key]);
        }

        $this->data[$key][] = $value;
    }

    public function hasData(string $key): bool
    {
       return array_key_exists($key, $this->data);
    }

    public function getData(string $key): array
    {
        return $this->data[$key] ?? [];
    }

    public function getLastData(string $key, $default = 0): float
    {
        if (!array_key_exists($key, $this->data)) {
            return $default;
        } else {
            return end($this->data[$key]) ?? $default;
        }
    }

    public function addDataSet(array $dataset): void
    {
        foreach ($dataset as $key => $value) {
            $this->addData($key, $value);
        }
    }

    public function getNumberOfGreaterThan(string $key, int $threshold): int
    {
        if (!array_key_exists($key, $this->data)) return 0;

        $count = 0;

        foreach ($this->data[$key] as $value) {
            if ($value >= $threshold) $count++;
        }

        return $count;
    }

    public function getDataSet(): array
    {
        return $this->data;
    }

    public function setCollection(array $collection): void
    {
        $this->collection = $collection;
    }

    public function getCollection(): array
    {
        return $this->collection;
    }
}