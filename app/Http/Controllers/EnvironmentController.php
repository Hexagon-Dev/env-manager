<?php

namespace App\Http\Controllers;

use App\Models\Entry;
use Illuminate\Http\Request;

class EnvironmentController extends Controller
{
    public function show(Request $request, string $project, string $branch): ?string
    {
        $entries = Entry::query()
            ->where('project', $project)
            ->where('branch', $branch)
            ->get();

        if ($request->ajax()) {
            return $entries->toJson();
        }

        $response = null;

        foreach ($entries as $entry) {
            $response .= $entry['key'] . '=' . $entry['value'] . '\n';
        }

        return $response;
    }

    public function save(Request $request, string $project, string $branch): void
    {
        $text = $request->get('values');
        $strings = explode("\n", $text);

        foreach ($strings as $string) {
            if ($string !== '') {
                $variables = explode("=", $string, 2);

                Entry::query()->insert([
                    'key' => $variables[0],
                    'value' => $variables[1],
                    'project' => $project,
                    'branch' => $branch,
                    ]);
            }
        }
    }
}
