<?php

namespace Database\Seeders;

use App\Models\Entry;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run(): void
    {
        $default = [
            ['APP_NAME' => 'Laravel'],
            ['APP_ENV' => 'local'],
            ['APP_KEY' => 'base64:ANhAAMjAuwzF6Iab9JqbPfeJ0dX1RyCtqdyRfnJo4ps='],
            ['APP_DEBUG' => 'true'],
            ['APP_URL' => 'http://localhost'],
            ['LOG_CHANNEL' => 'stack'],
            ['LOG_DEPRECATIONS_CHANNEL' => 'null'],
            ['LOG_LEVEL' => 'debug'],
            ['DB_CONNECTION' => 'mysql'],
            ['DB_HOST' => 'mariadb'],
            ['DB_PORT' => 3306],
            ['DB_DATABASE' => 'env-manager'],
            ['DB_USERNAME' => 'root'],
            ['DB_PASSWORD' => 'password'],
            ['BROADCAST_DRIVER' => 'log'],
            ['CACHE_DRIVER' => 'file'],
            ['FILESYSTEM_DRIVER' => 'local'],
            ['QUEUE_CONNECTION' => 'sync'],
            ['SESSION_DRIVER' => 'file'],
            ['SESSION_LIFETIME' => 120],
            ['MEMCACHED_HOST' => '127.0.0.1'],
            ['REDIS_HOST' => '127.0.0.1'],
            ['REDIS_PASSWORD' => 'null'],
            ['REDIS_PORT' => 6379],
            ['REDIS_PASSWORD' => 'null'],
            ['MAIL_MAILER' => 'smtp'],
            ['MAIL_HOST' => 'mailhog'],
            ['MAIL_PORT' => 1025],
            ['MAIL_USERNAME' => 'null'],
            ['MAIL_PASSWORD' => 'null'],
            ['MAIL_ENCRYPTION' => 'null'],
            ['MAIL_FROM_ADDRESS' => 'null'],
            ['MAIL_FROM_NAME' => '"${APP_NAME}"'],
            ['AWS_ACCESS_KEY_ID' => ''],
            ['AWS_SECRET_ACCESS_KEY' => ''],
            ['AWS_DEFAULT_REGION' => 'us-east-1'],
            ['AWS_BUCKET' => ''],
            ['AWS_USE_PATH_STYLE_ENDPOINT' => 'false'],
            ['PUSHER_APP_ID' => ''],
            ['PUSHER_APP_KEY' => ''],
            ['PUSHER_APP_SECRET' => ''],
            ['PUSHER_APP_CLUSTER' => 'mt1'],
            ['MIX_PUSHER_APP_KEY' => '"${PUSHER_APP_KEY}"'],
            ['MIX_PUSHER_APP_CLUSTER' => '"${PUSHER_APP_CLUSTER}"'],
        ];

        foreach ($default as $value) {
            Entry::query()->insert([
                'project' => 'default',
                'branch' => 'default',
                'key' => key($value),
                'value' => $value[key($value)],
            ]);
        }
    }
}
