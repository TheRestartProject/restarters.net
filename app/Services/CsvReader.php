<?php

namespace App\Services;

class CsvReader
{
    public function read(string $path): array
    {
        if (!is_readable($path)) {
            throw new \InvalidArgumentException("CSV file not readable: {$path}");
        }

        $handle = fopen($path, 'r');
        if (!$handle) {
            throw new \RuntimeException("Unable to open CSV file: {$path}");
        }

        $headers = fgetcsv($handle);
        if (!$headers) {
            fclose($handle);
            return [];
        }

        $headers = array_map(fn ($h) => $this->normaliseHeader((string) $h), $headers);

        $rows = [];

        while (($data = fgetcsv($handle)) !== false) {
            $row = [];
            foreach ($headers as $i => $header) {
                $row[$header] = isset($data[$i]) ? trim((string) $data[$i]) : '';
            }
            $rows[] = $row;
        }

        fclose($handle);

        return $rows;
    }

    protected function normaliseHeader(string $header): string
    {
        $header = trim(mb_strtolower($header));

        $map = [
            'what_is_it' => 'what is it?',
            'what is it' => 'what is it?',
            'event link optional' => 'event link (optional)',
        ];

        return $map[$header] ?? $header;
    }
}
