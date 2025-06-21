<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class PrometheusService
{
    private string $host;

    public function __construct(string $prometheusHost) {
        $this->host = rtrim($prometheusHost, '/');
    }

    public function query(string $expr) {
        $response = Http::get($this->host. "/api/v1/query", [
            'query' => $expr
        ]);

        return response()->json('data.result.0.value.1') ?? null;
    }

    public function getInstanceStat(string $instance): array
    {
        return [
            'cpu_load' => (float) $this->query("rate(node_cpu_seconds_total{mode!=\"idle\",instance=\"$instance\"}[1m])"),
            'ram_available' => (float) $this->query("node_memory_MemAvailable_bytes{instance=\"$instance\"}"),
            'ram_total' => (float) $this->query("node_memory_MemTotal_bytes{instance=\"$instance\"}"),
            'disk_available' => (float) $this->query("node_filesystem_avail_bytes{mountpoint=\"/\",instance=\"$instance\"}")
        ];
    }
}