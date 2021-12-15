<?php

namespace Tests\Feature;

use App\Environment;
use App\Models\Entry;
use Tests\TestCase;

class EnvTest extends TestCase
{
    public Environment $environment;

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

    public function __construct(?string $name = null, array $data = [], $dataName = '')
    {
        $this->environment = new Environment();
        parent::__construct($name, $data, $dataName);
    }

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

        $strings = explode("\n", $data);

        $test = null;

        foreach ($strings as $string) {
            if ($string !== '') {
                $variables = explode("=", $string, 2);

                $test[] = [$variables[0] => $variables[1]];
            }
        }

        self::assertEquals($test, $this->modified_data);
    }

    /** @test */
    public function checkHasComment(): void
    {
        $actual = $this->environment->checkComment('test#comment');

        self::assertEquals('test', $actual);
    }

    /** @test */
    public function checkEmptyComment(): void
    {
        $actual = $this->environment->checkComment('#comment');

        self::assertEquals('', $actual);
    }

    /** @test */
    public function checkNoComment(): void
    {
        $actual = $this->environment->checkComment('test');

        self::assertEquals(false, $actual);
    }

    /** @test */
    public function checkHasQuotes(): void
    {
        $actual = $this->environment->checkQuotes('"test#123"');

        self::assertEquals('test#123', $actual);
    }

    /** @test */
    public function checkEmptyQuotes(): void
    {
        $actual = $this->environment->checkQuotes('""');

        self::assertEquals('', $actual);
    }

    /** @test */
    public function checkNoQuotes(): void
    {
        $actual = $this->environment->checkQuotes('test#123');

        self::assertEquals(false, $actual);
    }

    /** @test */
    public function splitLines(): void
    {
        $actual = $this->environment->splitLines($this->data);

        self::assertEquals(array_filter(explode("\n", $this->data)), $actual);
    }

    /** @test */
    public function splitVariables(): void
    {
        $actual = $this->environment->splitVariables('APP_NAME=Laravel');

        self::assertEquals(explode('=', 'APP_NAME=Laravel', 2), $actual);
    }

    /** @test */
    public function splitVariables1(): void
    {
        $actual = $this->environment->splitVariables('APP_NAME=');
    }
}
