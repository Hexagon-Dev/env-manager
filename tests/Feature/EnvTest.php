<?php

namespace Tests\Feature;

use App\Models\Entry;
use Tests\TestCase;

class EnvTest extends TestCase
{
    public string $data =
        <<< HEREDOC
        APP_NAME=Laravel
        APP_ENV=local
        APP_KEY=base64:ANhAAMjAuwzF6Iab9JqbPfeJ0dX1RyCtqdyRfnJo4ps=
        APP_DEBUG=true
        APP_URL=http://localhost

        LOG_CHANNEL=stack
        LOG_DEPRECATIONS_CHANNEL=null
        LOG_LEVEL=debug
        HEREDOC;

    public array $modified_data = [
        ['APP_NAME' => 'Laravel'],
        ['APP_ENV' => 'local'],
        ['APP_KEY' => 'base64:ANhAAMjAuwzF6Iab9JqbPfeJ0dX1RyCtqdyRfnJo4ps='],
        ['APP_DEBUG' => 'true'],
        ['APP_URL' => 'http://localhost'],
        ['LOG_CHANNEL' => 'stack'],
        ['LOG_DEPRECATIONS_CHANNEL' => 'null'],
        ['LOG_LEVEL' => 'debug'],
    ];

    /** @test */
    public function saveEnv(): void
    {
        $this->post('/api/test/master', ['values' => trim($this->data, '"')]);

        $values = Entry::query()
            ->where('project', 'test')
            ->where('branch', 'master')
            ->get()
            ->toArray();

        $test = null;

        foreach ($values as $item) {
            $test[] = [$item['key'] => $item['value']];
        }

        Entry::query()
            ->where('project', 'test')
            ->where('branch', 'master')
            ->delete();

        self::assertEquals($test, $this->modified_data);
    }

    /** @test */
    public function loadEnv(): void
    {
        foreach ($this->modified_data as $value) {
            Entry::query()->insert([
               'project' => 'test',
               'branch' => 'master',
               'key' => key($value),
               'value' => $value[key($value)],
            ]);
        }

        $data = $this->get('/api/test/master')->content();

        Entry::query()
            ->where('project', 'test')
            ->where('branch', 'master')
            ->delete();

        $strings = explode('\n', $data);

        $test = null;

        foreach ($strings as $string) {
            if ($string !== '') {
                $variables = explode("=", $string, 2);

                $test[] = [$variables[0] => $variables[1]];
            }
        }

        self::assertEquals($test, $this->modified_data);
    }
}
