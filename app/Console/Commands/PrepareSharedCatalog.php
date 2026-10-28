<?php

namespace App\Console\Commands;

use Dotenv\Dotenv;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use PDO;
use RuntimeException;

#[Signature('heavypack:prepare-shared {--activate : Connect the local website and admin after tests pass} {--merge-development : Preserve reviewed edits from the initial development catalog}')]
#[Description('Back up local HeavyPack, prepare separate admin/test databases, and connect shared catalog data.')]
class PrepareSharedCatalog extends Command
{
    private const INTERNAL_TABLES = ['admin_users', 'admin_password_reset_tokens', 'admin_sessions', 'cache', 'cache_locks', 'jobs', 'job_batches', 'failed_jobs', 'migrations'];

    private PDO $setupDatabase;

    private array $state;

    public function handle(): int
    {
        if (! app()->isLocal()) {
            throw new RuntimeException('This command is restricted to the local XAMPP environment.');
        }
        $websiteRoot = realpath(config('heavypack.website_root'));
        $workspace = realpath(dirname(base_path()));
        if (! $websiteRoot || ! $workspace || ! str_starts_with($websiteRoot, $workspace.DIRECTORY_SEPARATOR)) {
            throw new RuntimeException('Website root must be inside the XAMPP htdocs workspace.');
        }
        $websiteEnvironment = Dotenv::parse(File::get($websiteRoot.'/derie26/.env'));
        if (($websiteEnvironment['DB_DATABASE'] ?? '') !== 'heavypack'
            || ! in_array($websiteEnvironment['DB_HOST'] ?? '', ['localhost', '127.0.0.1'], true)) {
            throw new RuntimeException('Expected the local heavypack website database.');
        }
        $statePath = base_path('.runtime/shared-catalog/state.json');
        File::ensureDirectoryExists(dirname($statePath));
        $this->setupDatabase = new PDO('mysql:host=127.0.0.1;port=3306;charset=utf8mb4', 'root', '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

        if (File::exists($statePath)) {
            $this->state = json_decode(File::get($statePath), true, flags: JSON_THROW_ON_ERROR);
        } else {
            $this->state = [
                'backup' => base_path('.runtime/shared-catalog/backups/'.now('Asia/Jakarta')->format('Ymd-His')),
                'passwords' => [
                    'admin' => bin2hex(random_bytes(24)), 'catalog' => bin2hex(random_bytes(24)),
                    'website' => bin2hex(random_bytes(24)), 'test' => bin2hex(random_bytes(24)),
                ],
            ];
            File::ensureDirectoryExists($this->state['backup']);
            File::copy(base_path('.env'), $this->state['backup'].'/admin.env');
            File::copy($websiteRoot.'/derie26/.env', $this->state['backup'].'/website.env');
            foreach (['heavypack', 'heavypack_filament_dev'] as $database) {
                $result = Process::timeout(120)->run([
                    'C:/xampp/mysql/bin/mysqldump.exe', '-u', 'root', '-h', '127.0.0.1',
                    '--default-character-set=utf8mb4', '--lock-all-tables', '--routines', '--triggers',
                    '--databases', $database, '--result-file='.$this->state['backup'].'/'.$database.'.sql',
                ]);
                if (! $result->successful()) {
                    throw new RuntimeException('Database backup failed: '.$result->errorOutput());
                }
            }
            foreach (['produk-hv', 'customer-hv', 'article', 'images'] as $directory) {
                $this->copyTree($websiteRoot.'/'.$directory, $this->state['backup'].'/media/'.$directory);
            }
            File::put($statePath, json_encode($this->state, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
        }

        $this->createDatabase('heavypack_admin');
        $this->copyTables('heavypack_filament_dev', 'heavypack_admin', self::INTERNAL_TABLES);
        $this->createDatabase('heavypack_admin_test');
        $this->copyTables('heavypack_admin', 'heavypack_admin_test', self::INTERNAL_TABLES);
        $this->createDatabase('heavypack_catalog_test');
        $sourceTables = $this->tableNames('heavypack');
        $this->copyTables('heavypack', 'heavypack_catalog_test', $sourceTables);
        $this->ensureTransactions('heavypack_catalog_test', $sourceTables);

        $this->createAccount('heavypack_admin', $this->state['passwords']['admin']);
        $this->grant('SELECT, INSERT, UPDATE, DELETE', 'heavypack_admin', '*', 'heavypack_admin');
        $this->createAccount('heavypack_test', $this->state['passwords']['test']);
        foreach (['heavypack_admin_test', 'heavypack_catalog_test'] as $database) {
            $this->grant('SELECT, INSERT, UPDATE, DELETE', $database, '*', 'heavypack_test');
        }

        $this->preparePreview($websiteRoot, $websiteEnvironment);
        $adminEnvironment = Dotenv::parse(File::get($this->state['backup'].'/admin.env'));
        $this->writeEnvironment(base_path('.env.testing'), [
            ...$adminEnvironment,
            'APP_ENV' => 'testing', 'DB_DATABASE' => 'heavypack_admin_test',
            'DB_USERNAME' => 'heavypack_test', 'DB_PASSWORD' => $this->state['passwords']['test'],
            'CATALOG_DB_DATABASE' => 'heavypack_catalog_test', 'CATALOG_DB_HOST' => '127.0.0.1',
            'CATALOG_DB_USERNAME' => 'heavypack_test', 'CATALOG_DB_PASSWORD' => $this->state['passwords']['test'],
            'LEGACY_MEDIA_ROOT' => config('heavypack.preview_root'),
            'LEGACY_MEDIA_URL' => config('heavypack.preview_url'),
            'HEAVYPACK_CONTENT_MODE' => 'testing', 'HEAVYPACK_ENVIRONMENT_LABEL' => 'Katalog pengujian',
        ]);

        if ($this->option('activate')) {
            $this->activate($websiteRoot, $websiteEnvironment);
        }
        $this->info('Backup: '.$this->state['backup']);
        $this->info('Admin database: heavypack_admin; test databases: heavypack_admin_test and heavypack_catalog_test.');
        $this->info('Preview root: '.config('heavypack.preview_root'));

        return self::SUCCESS;
    }

    private function activate(string $websiteRoot, array $websiteEnvironment): void
    {
        if (isset($this->state['activated'])) {
            $this->info('Shared catalog is already active.');

            return;
        }
        if ($this->option('merge-development')) {
            $this->mergeDevelopmentEdits($websiteRoot);
        }
        foreach (config('legacy') as $definition) {
            if ($definition['readOnly'] ?? false) {
                continue;
            }
            $table = $this->identifier($definition['table']);
            $primaryKey = $this->identifier($definition['pk']);
            $original = $this->setupDatabase->query("SELECT * FROM heavypack.$table ORDER BY $primaryKey")->fetchAll(PDO::FETCH_ASSOC);
            $development = $this->setupDatabase->query("SELECT * FROM heavypack_filament_dev.$table ORDER BY $primaryKey")->fetchAll(PDO::FETCH_ASSOC);
            if ($original !== $development && ! $this->option('merge-development')) {
                throw new RuntimeException("Development changes found in $table. Both copies were backed up; reconcile this table before activating.");
            }
        }

        $this->ensureTransactions('heavypack', array_column(config('legacy'), 'table'));
        $this->createAccount('heavypack_content', $this->state['passwords']['catalog']);
        foreach (config('legacy') as $definition) {
            $rights = ($definition['readOnly'] ?? false)
                ? (($definition['allowDelete'] ?? false) ? 'SELECT, DELETE' : 'SELECT')
                : 'SELECT, INSERT, UPDATE, DELETE';
            $this->grant($rights, 'heavypack', $definition['table'], 'heavypack_content');
        }
        $this->grant('SELECT, UPDATE', 'heavypack', 'video_produks', 'heavypack_content');

        $this->createAccount('heavypack_web', $this->state['passwords']['website']);
        foreach ($this->tableNames('heavypack') as $table) {
            if ($table !== 'admins') {
                $this->grant('SELECT', 'heavypack', $table, 'heavypack_web');
            }
        }
        foreach (['users', 'password_resets', 'pemesanans', 'cart', 'contacts', 'services', 'warrantys', 'komentar_articles', 'emails', 'click_whatsapps', 'visitor', 'used_kupons'] as $table) {
            $this->grant('INSERT, UPDATE, DELETE', 'heavypack', $table, 'heavypack_web');
        }

        $adminEnvironment = Dotenv::parse(File::get(base_path('.env')));
        $this->writeEnvironment(base_path('.env'), [
            ...$adminEnvironment,
            'DB_DATABASE' => 'heavypack_admin', 'DB_USERNAME' => 'heavypack_admin',
            'DB_PASSWORD' => $this->state['passwords']['admin'],
            'CATALOG_DB_HOST' => '127.0.0.1', 'CATALOG_DB_PORT' => '3306',
            'CATALOG_DB_DATABASE' => 'heavypack', 'CATALOG_DB_USERNAME' => 'heavypack_content',
            'CATALOG_DB_PASSWORD' => $this->state['passwords']['catalog'],
            'LEGACY_MEDIA_ROOT' => $websiteRoot, 'LEGACY_MEDIA_URL' => config('heavypack.website_url'),
            'HEAVYPACK_CONTENT_MODE' => 'shared', 'HEAVYPACK_ENVIRONMENT_LABEL' => 'Katalog bersama — website lokal',
        ]);
        $this->writeEnvironment($websiteRoot.'/derie26/.env', [
            ...$websiteEnvironment, 'DB_HOST' => '127.0.0.1', 'DB_USERNAME' => 'heavypack_web',
            'DB_PASSWORD' => $this->state['passwords']['website'], 'APP_URL' => config('heavypack.website_url'),
        ]);
        $this->state['activated'] = now('Asia/Jakarta')->toIso8601String();
        File::put(base_path('.runtime/shared-catalog/state.json'), json_encode($this->state, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
        $this->info('Local shared catalog activated. Admin login accounts were retained.');
    }

    private function mergeDevelopmentEdits(string $websiteRoot): void
    {
        $changes = [];
        foreach (config('legacy') as $definition) {
            if ($definition['readOnly'] ?? false) {
                continue;
            }
            $table = $this->identifier($definition['table']);
            $primaryKey = $this->identifier($definition['pk']);
            $original = $this->setupDatabase->query("SELECT * FROM heavypack.$table ORDER BY $primaryKey")->fetchAll(PDO::FETCH_ASSOC);
            $baseline = $this->setupDatabase->query("SELECT * FROM heavypack_catalog_test.$table ORDER BY $primaryKey")->fetchAll(PDO::FETCH_ASSOC);
            $development = $this->setupDatabase->query("SELECT * FROM heavypack_filament_dev.$table ORDER BY $primaryKey")->fetchAll(PDO::FETCH_ASSOC);
            if ($original !== $baseline) {
                throw new RuntimeException("Concurrent website edits found in $table; merge requires review.");
            }
            if (array_column($original, $primaryKey) !== array_column($development, $primaryKey)) {
                throw new RuntimeException("Added or deleted development records in $table require review.");
            }
            foreach ($original as $index => $row) {
                $delta = [];
                foreach ($row as $column => $value) {
                    if ($column !== $primaryKey && ! in_array($column, ['created_at', 'updated_at'], true) && $value !== $development[$index][$column]) {
                        $delta[$column] = $development[$index][$column];
                    }
                }
                if ($delta !== []) {
                    if (array_key_exists('updated_at', $row)) {
                        $delta['updated_at'] = $development[$index]['updated_at'];
                    }
                    $changes[] = ['table' => $table, 'pk' => $primaryKey, 'id' => $row[$primaryKey], 'values' => $delta, 'original' => $row, 'references' => $definition['references'] ?? []];
                }
            }
        }
        $this->ensureTransactions('heavypack', array_column(config('legacy'), 'table'));
        $this->setupDatabase->beginTransaction();
        try {
            foreach ($changes as $change) {
                $assignments = implode(', ', array_map(fn (string $column): string => $this->identifier($column).'=?', array_keys($change['values'])));
                $query = $this->setupDatabase->prepare('UPDATE heavypack.'.$change['table'].' SET '.$assignments.' WHERE '.$change['pk'].'=?');
                $query->execute([...array_values($change['values']), $change['id']]);
                foreach ($change['references'] as $reference) {
                    $field = $reference['value'] ?? null;
                    if (($reference['propagate'] ?? false) && $field && array_key_exists($field, $change['values'])) {
                        $table = $this->identifier($reference['table']);
                        $column = $this->identifier($reference['column']);
                        $query = $this->setupDatabase->prepare("UPDATE heavypack.$table SET $column=? WHERE $column=?");
                        $query->execute([$change['values'][$field], $change['original'][$field]]);
                    }
                }
                $this->line('Preserved development edit: '.$change['table'].' #'.$change['id']);
            }
            $this->setupDatabase->commit();
        } catch (\Throwable $exception) {
            $this->setupDatabase->rollBack();
            throw $exception;
        }
    }

    private function preparePreview(string $websiteRoot, array $websiteEnvironment): void
    {
        $preview = config('heavypack.preview_root');
        if (! is_dir($preview)) {
            $arguments = ['robocopy', $websiteRoot, $preview, '/E', '/XJ', '/COPY:DAT', '/R:1', '/W:1', '/NFL', '/NDL', '/NJH', '/NJS', '/NP', '/XD'];
            foreach (['.git', '.agents', '.codex', 'node_modules', 'hcga', 'helpdesk', 'database', 'DBx', 'bawaanAdmin', 'bawaanLink', 'deploy', 'tools', 'tmp', 'cgi-bin', 'derie26/node_modules', 'derie26/storage'] as $directory) {
                $arguments[] = $websiteRoot.'/'.$directory;
            }
            $result = Process::timeout(180)->run($arguments);
            if ($result->exitCode() >= 8) {
                throw new RuntimeException('Could not copy the local website preview.');
            }
            foreach (['app', 'logs', 'framework/cache/data', 'framework/sessions', 'framework/views'] as $directory) {
                File::ensureDirectoryExists($preview.'/derie26/storage/'.$directory);
            }
            File::put($preview.'/derie26/server.php', <<<'PHP'
<?php
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
$root = realpath(dirname(__DIR__));
$path = realpath($root.$uri);
$extension = strtolower(pathinfo($uri, PATHINFO_EXTENSION));
$allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'css', 'js', 'ico', 'json', 'woff', 'woff2', 'ttf', 'eot', 'pdf'];
if (strpos($uri, '/derie26') === 0 || strpos($uri, '/.') !== false) {
    http_response_code(404);
    return true;
}
if ($path && strpos($path, $root.DIRECTORY_SEPARATOR) === 0 && is_file($path) && in_array($extension, $allowed, true)) {
    $types = ['css' => 'text/css', 'js' => 'application/javascript', 'svg' => 'image/svg+xml', 'pdf' => 'application/pdf'];
    header('Content-Type: '.(isset($types[$extension]) ? $types[$extension] : mime_content_type($path)));
    readfile($path);
    return true;
}
require $root.'/index.php';
PHP);
        }
        $this->writeEnvironment($preview.'/derie26/.env', [
            ...$websiteEnvironment, 'APP_ENV' => 'local', 'APP_URL' => config('heavypack.preview_url'),
            'DB_HOST' => '127.0.0.1', 'DB_DATABASE' => 'heavypack_catalog_test',
            'DB_USERNAME' => 'heavypack_test', 'DB_PASSWORD' => $this->state['passwords']['test'],
            'MAIL_DRIVER' => 'log', 'SESSION_COOKIE' => 'heavypack_preview_session',
            'GOOGLE_ID' => '', 'GOOGLE_SECRET' => '', 'FB_ID' => '', 'FB_SECRET' => '',
            'TWITTER_ID' => '', 'TWITTER_SECRET' => '',
        ]);
    }

    private function createDatabase(string $database): void
    {
        $database = $this->identifier($database);
        $this->setupDatabase->exec("CREATE DATABASE IF NOT EXISTS $database CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    }

    private function tableNames(string $database): array
    {
        $query = $this->setupDatabase->prepare('SELECT table_name FROM information_schema.tables WHERE table_schema=? AND table_type=? ORDER BY table_name');
        $query->execute([$database, 'BASE TABLE']);

        return $query->fetchAll(PDO::FETCH_COLUMN);
    }

    private function copyTables(string $source, string $destination, array $tables): void
    {
        $existing = $this->tableNames($destination);
        foreach ($tables as $table) {
            $table = $this->identifier($table);
            if (! in_array($table, $existing, true)) {
                $this->setupDatabase->exec("CREATE TABLE $destination.$table LIKE $source.$table");
                $this->setupDatabase->exec("INSERT INTO $destination.$table SELECT * FROM $source.$table");
            }
        }
    }

    private function ensureTransactions(string $database, array $tables): void
    {
        foreach ($tables as $table) {
            $table = $this->identifier($table);
            $query = $this->setupDatabase->prepare('SELECT engine FROM information_schema.tables WHERE table_schema=? AND table_name=?');
            $query->execute([$database, $table]);
            if ($query->fetchColumn() !== 'InnoDB') {
                $this->setupDatabase->exec("ALTER TABLE $database.$table ENGINE=InnoDB");
            }
        }
    }

    private function createAccount(string $username, string $password): void
    {
        $username = $this->identifier($username);
        $this->setupDatabase->exec("CREATE USER IF NOT EXISTS '$username'@'127.0.0.1' IDENTIFIED BY ".$this->setupDatabase->quote($password));
    }

    private function grant(string $rights, string $database, string $table, string $username): void
    {
        $database = $this->identifier($database);
        $table = $table === '*' ? '*' : $this->identifier($table);
        $username = $this->identifier($username);
        $this->setupDatabase->exec("GRANT $rights ON $database.$table TO '$username'@'127.0.0.1'");
    }

    private function identifier(string $identifier): string
    {
        if (! preg_match('/^[a-z_]+$/', $identifier)) {
            throw new RuntimeException('Invalid setup identifier.');
        }

        return $identifier;
    }

    private function copyTree(string $source, string $destination): void
    {
        $result = Process::timeout(180)->run(['robocopy', $source, $destination, '/E', '/XJ', '/COPY:DAT', '/R:1', '/W:1', '/NFL', '/NDL', '/NJH', '/NJS', '/NP']);
        if ($result->exitCode() >= 8) {
            throw new RuntimeException('Media backup failed.');
        }
    }

    private function writeEnvironment(string $path, array $values): void
    {
        $lines = [];
        foreach ($values as $key => $value) {
            $lines[] = $key.'="'.str_replace(['\\', '"', "\n", "\r", '$'], ['\\\\', '\\"', '\\n', '', '\\$'], (string) $value).'"';
        }
        File::put($path, implode("\n", $lines)."\n");
    }
}
