<?php

namespace Tests\Feature;

use App\Environment;
use App\Models\Entry;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;
use Throwable;

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
        $this->post('/api/test/master', ['values' => $this->data], ['X-Requested-With' => 'XMLHttpRequest'])
             ->assertStatus(Response::HTTP_OK);

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
    public function saveInvalid(): void
    {
        $this->post('/api/test/master', ['values' => 'ertwete ewrt'], ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertJson(['error' => 'Line is invalid'])
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    /** @test */
    public function saveWithoutAJAX(): void
    {
        $this->post('/api/test/master', ['values' => 'ertwete=ewrt'],)
            ->assertJson(['error' => 'access denied'])
            ->assertStatus(Response::HTTP_FORBIDDEN);
    }

    /** @test */
    public function saveInvalidSymbols(): void
    {
        $this->post('/api/test/master', ['values' => '!@#$%^&*()_-="!@#$%^&*()_+"'], ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertJson(['error' => 'Line is invalid'])
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    /** @test */
    public function saveFirstValidSecondInvalid(): void
    {
        $this->post('/api/test/master', ['values' => "ertwete=ewrt\nasdasd asd"], ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertJson(['error' => 'Line is invalid'])
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
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

        $data = $this->get('/api/test/master')
            ->assertStatus(Response::HTTP_OK)
            ->content();

        Entry::query()
            ->where('project', 'test')
            ->where('branch', 'master')
            ->delete();

        $strings = $this->environment->splitLines($data);
        $test = [];

        foreach ($strings as $string) {
            $variables = $this->environment->splitVariables($string);
            if ($var = $this->environment->checkQuotes($variables[1])) {
                $variables[1] = $var;
            } else if ($var = $this->environment->checkComment($variables[1])) {
                $variables[1] = $var;
            }

            $test[trim($variables[0])] = str_replace('\\', '', trim($variables[1]));
        }

        $data = [];

        foreach ($this->modified_data as $var) {
            $key = key($var);

            $data[$key] = $var[$key];
        }

        self::assertEquals($test, $data);
    }

    /**
     * @test
     * @throws Throwable
     */
    public function loadJsonEnv(): void
    {
        foreach ($this->modified_data as $value) {
            Entry::query()->insert([
                'project' => 'test',
                'branch' => 'master',
                'key' => key($value),
                'value' => $value[key($value)],
            ]);
        }

        $data = $this->get('/api/test/master', ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertStatus(Response::HTTP_OK)
            ->content();

        Entry::query()
            ->where('project', 'test')
            ->where('branch', 'master')
            ->delete();

        $data = json_decode($data, JSON_THROW_ON_ERROR, 512, JSON_THROW_ON_ERROR);

        $test = [];

        foreach ($data as $key => $value) {
            $value = str_replace(array('"', "\\"), '', json_encode($value, JSON_THROW_ON_ERROR));
            $test[] = [$key => $value];
        }

        self::assertEquals($test, $this->modified_data);
    }

    /** @test */
    public function loadNonExistentEnv(): void
    {
        $this->get('/api/fgsdfgds/sdfgsdfgsdf')
            ->assertStatus(Response::HTTP_NOT_FOUND);
    }

    /**
     * @test
     * @dataProvider commentProvider
     */
    public function checkComment($text, $expected): void
    {
        $actual = $this->environment->checkComment($text);

        self::assertEquals($expected, $actual);
    }

    public function commentProvider(): array
    {
        return [
            'empty'  => ['', ''],
            'has comment' => ['test#comm', 'test'],
            'has two comments' => ['test#comm#comm', 'test'],
            'various symbols in comment'  => ['test#@#$%&^%$*)_+1', 'test'],
            'no comment' => ['test', false],
            'comment with rus' => ['тест#ком', 'тест'],
            'both are various symbols' => ['-12"№;%:*)#test#test', '-12"№;%:*)'],
        ];
    }

    /**
     * @test
     * @dataProvider quoteProvider
     */
    public function checkQuotes($text, $expected): void
    {
        $actual = $this->environment->checkQuotes($text);

        self::assertEquals($expected, $actual);
    }

    public function quoteProvider(): array
    {
        return [
            'empty'  => ['""', ''],
            'has quotes' => ['"test#123"', 'test#123'],
            'has quotes and symbols' => ['"test#123!@#$%^&*()_+"', 'test#123!@#$%^&*()_+'],
            'no quotes' => ['test#123', false],
            'no quotes but symbols' => ['test#123!@#$%^&*()_+#$', false],
            'quotes with comment' => ['"test"123!@#$%^&*()_+#$', 'test'],
            'double quotes with comment' => ['"test"123!"@#$%"^&*()_+#$', 'test'],
        ];
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
    public function splitEmptyVariables(): void
    {
        $actual = $this->environment->splitVariables('');

        self::assertEquals(false, $actual);
    }
}
