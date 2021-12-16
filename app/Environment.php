<?php

namespace App;

use App\Models\Entry;
use Facade\FlareClient\Http\Exceptions\NotFound;
use Illuminate\Support\Collection;
use RuntimeException;
use Throwable;

class Environment
{
    public string $project;

    /**
     * @throws Throwable
     */
    public function show(bool $is_ajax, string $project, string $branch): ?string
    {
        $this->project = $project;

        $project_defaults = $this->getEntries();
        $project_entries = $this->getEntries($branch);

        if ($project_entries->isEmpty()) {
            throw new NotFound();
        }

        $project_entries = $this->castTypes($project_defaults->replace($project_entries));

        if ($is_ajax) {
            return $project_entries->toJson();
        }

        $response = null;

        foreach ($project_entries->toArray() as $key => $value) {
            $response .= $key . '=' . str_replace('\\', '', json_encode($value, JSON_THROW_ON_ERROR)) . "\n";
        }

        return $response;
    }

    /**
     * @throws Throwable
     */
    public function save(string $text, string $project, string $branch): void
    {
        $this->project = $project;

        $strings = $this->splitLines($text);
        $this->insertGiven($strings, $branch);
    }

    public function getEntries(string $branch = 'default'): Collection
    {
        return $this->makePaired(
            Entry::query()
                ->where('project', $this->project)
                ->where('branch', $branch)
                ->get()
                ->collect()
        );
    }

    public function makePaired(Collection $pairs): Collection
    {
        $result = [];
        $pairs->each(function($item) use (&$result) {
            if (is_array($item)) {
                $item = (object) $item;
            }
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

            if (!is_null(filter_var($value, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE))) {
                return filter_var($value, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);
            }

            if (is_numeric($value)) {
                return filter_var($value, FILTER_VALIDATE_INT);
            }

            return $value;
        });
    }

    /**
     * @throws Throwable
     */
    public function insertGiven(array $strings, string $branch): void
    {
        foreach ($strings as $string) {
            if ($var = $this->splitVariables($string)) {
                $variables = $var;
            } else {
                throw new RuntimeException('Line is invalid');
            }

            if ($var = $this->checkQuotes($variables[1])) {
                $variables[1] = $var;
            }

            Entry::query()->insert([
                'key' => trim($variables[0]),
                'value' => trim($variables[1]),
                'project' => $this->project,
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
        if ($var = $this->checkComment($line)) {
            if (!str_contains($var[0], '=')) {
                return false;
            }

            $return = explode('=', $var[0], 2);
            $return[1] .= $var[1];

            return $return;
        }

        if (!str_contains($line, '=')) {
            return false;
        }

        return explode('=', $line, 2);
    }
}
