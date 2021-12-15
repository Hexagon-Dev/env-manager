<?php

namespace App;

use App\Models\Entry;
use Illuminate\Support\Collection;

class Environment
{
    public function show(bool $is_ajax, string $project, string $branch): ?string
    {
        $project_defaults = $this->getEntries($project);
        $project_entries = $this->getEntries($project, $branch);

        $project_entries = $this->castTypes($project_defaults->replace($project_entries));

        if ($is_ajax) {
            return $project_entries->toJson();
        }

        $response = null;

        foreach ($project_entries->toArray() as $key => $value) {
            $response .= $key . '=' . $value . "\n";
        }

        return $response;
    }

    public function save(string $text, string $project, string $branch): void
    {
        $strings = $this->splitLines($text);
        $this->insertGiven($strings, $project, $branch);
    }

    public function getEntries(string $project = 'default', string $branch = 'default'): Collection
    {
        return $this->makePaired(
            Entry::query()
                ->where('project', $project)
                ->where('branch', $branch)
                ->get()
                ->collect()
        );
    }

    public function makePaired(Collection $pairs): Collection
    {
        $result = [];
        $pairs->each(function($item) use (&$result) {
            $result[$item->key] = $item->value;
        });
        return collect($result);
    }

    public function castTypes(Collection $collection) : Collection
    {
        return $collection->map(function ($value) {

            if ($value === '') {
                return $value;
            }

            if ($value === 'null') {
                return null;
            }

            if (is_numeric($value)) {
                return filter_var($value, FILTER_VALIDATE_INT);
            }

            if (!is_null(filter_var($value, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE))) {
                return filter_var($value, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);
            }

            return $value;
        });
    }

    public function insertGiven(array $strings, string $project, string $branch): void
    {
        foreach ($strings as $string) {
            $variables = $this->splitVariables($string);

            if ($var = $this->checkQuotes($variables[1])) {
                $variables[1] = $var;
            } else if ($var = $this->checkComment($variables[1])) {
                $variables[1] = $var;
            }

            Entry::query()->insert([
                'key' => trim($variables[0]),
                'value' => trim($variables[1]),
                'project' => $project,
                'branch' => $branch,
            ]);
        }
    }

    public function checkComment(string $line)
    {
        if (strpos(trim($line), '#')) {
            return explode('#', trim($line))[0];
        }

        return false;
    }

    public function checkQuotes(string $line)
    {
        if (strpos($line, '"') === 0) {
            return explode('"', trim($line))[1];
        }

        return false;
    }

    public function splitLines(string $text): array
    {
        return array_filter(explode("\n", $text));
    }

    public function splitVariables(string $line)
    {
        return explode('=', $line, 2);
    }
}
