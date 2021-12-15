<?php

namespace App;

use App\Models\Entry;
use Illuminate\Support\Collection;

class Environment
{

    public function pairify(Collection $pairs): array
    {
        $result = [];
        $pairs->each(function($item) use (&$result) {
            $result[$item->key] = $item->value;
        });
        return $result;
    }

    public function show(bool $is_ajax, string $project, string $branch): ?string
    {
        $defaults = collect([]);

        $entries = Entry::query()
            ->where('project', $project)
            ->where('branch', $branch)
            ->get();

        $values = array_replace($this->pairify($defaults), $this->pairify($entries));
        dd((bool)$values["APP_DEBUG"]);
        dd($values);

        if ($is_ajax) {
            return $entries->toJson();
        }

        $response = null;

        foreach ($entries as $entry) {
            $response .= $entry['key'] . '=' . $entry['value'] . "\n";
        }

        return $response;
    }

    public function save(string $text, string $project, string $branch): void
    {
        $strings = $this->splitLines($text);
        $inserted = $this->insertGiven($strings, $project, $branch);
        $this->setDefaults($project, $branch, $inserted);
    }

    public function insertGiven(array $strings, string $project, string $branch): array
    {
        $inserted = null;

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

            $inserted[] = [trim($variables[0]) => trim($variables[1])];
        }

        return $inserted;
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

    public function getDefaults(string $project, array $inserted): array
    {
        $proj_defaults = $this->getProjectDefaults($project, $inserted);
        $main_defaults = $this->getGlobalDefaults($inserted);

        return $this->mergeDefaults($proj_defaults, $main_defaults);
    }

    public function getProjectDefaults(string $project, array $inserted): array
    {
        $project_defaults = Entry::query()
            ->where('project', $project)
            ->where('branch', 'default')
            ->get();
        dd($project_defaults);

        if (!$project_defaults->isEmpty()) {
            $project_defaults = $project_defaults->toArray();
            foreach ($inserted as $inserted_val) {
                foreach ($project_defaults as $proj_default) {
                    if (key($inserted_val) === $proj_default['key']) {
                        unset($project_defaults[array_search($proj_default, $project_defaults, true)]);
                    }
                }
            }
        }

        return $project_defaults;
    }

    public function getGlobalDefaults(array $inserted): array
    {
        $main_defaults = Entry::query()
            ->where('project', 'default')
            ->where('branch', 'default')
            ->get()
            ->toArray();

        foreach ($main_defaults as $default) {
            foreach ($inserted as $item) {
                if (isset($default) && $default['key'] === key($item)) {
                    unset($main_defaults[array_search($default, $main_defaults, true)]);
                }
            }
        }

        return $main_defaults;
    }

    public function mergeDefaults($proj_defaults, $main_defaults): array
    {
        foreach ($proj_defaults as $proj_default) {
            foreach ($main_defaults as $main_default) {
                if ($main_default['key'] === $proj_default['key']) {
                    $index = array_search($main_default['key'], $main_defaults, true);
                    $main_defaults[$index]['value'] = $proj_default['value'];
                }
            }
        }

        return $main_defaults;
    }

    public function setDefaults($project, $branch, $inserted): void
    {
        $defaults = $this->getDefaults($project, $inserted);

        foreach ($defaults as $default) {
            Entry::query()->insert([
                'key' => trim($default['key']),
                'value' => trim($default['value']),
                'project' => $project,
                'branch' => $branch,
            ]);
        }
    }
}

