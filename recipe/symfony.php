<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer;

require_once __DIR__ . '/common.php';


/**
 * Symfony Configuration
 */

// Symfony build set
set('symfony_env', 'prod');

// Symfony shared dirs
set('shared_dirs', ['app/logs']);

// Symfony web dir
set('web_dir', 'web');

// Symfony shared files
set('shared_files', ['app/config/parameters.yml']);

// Symfony writable dirs
set('writable_dirs', ['app/cache', 'app/logs']);

// Clear paths
set('clear_paths', [
    get('web_dir') .'/app_*.php', 
    get('web_dir') .'/config.php'
);

// Assets
set('assets', [
    get('web_dir') . '/css', 
    get('web_dir') . '/images', 
    get('web_dir') . '/js'
]);

// Requires non symfony-core package `kriswallsmith/assetic` to be installed
set('dump_assets', false);

// Environment vars
set('env', function () {
    return [
        'SYMFONY_ENV' => get('symfony_env')
    ];
});

// Adding support for the Symfony3 directory structure
set('bin_dir', 'app');
set('var_dir', 'app');

// Symfony console bin
set('bin/console', function () {
    return sprintf('{{release_path}}/%s/console', trim(get('bin_dir'), '/'));
});

// Symfony console opts
set('console_options', function () {
    $options = '--no-interaction --env={{symfony_env}}';
    return get('symfony_env') !== 'prod' ? $options : sprintf('%s --no-debug', $options);
});


/**
 * Create cache dir
 */
task('deploy:create_cache_dir', function () {
    // Set cache dir
    set('cache_dir', '{{release_path}}/' . trim(get('var_dir'), '/') . '/cache');

    // Remove cache dir if it exist
    run('if [ -d "{{cache_dir}}" ]; then rm -rf {{cache_dir}}; fi');

    // Create cache dir
    run('mkdir -p {{cache_dir}}');

    // Set rights
    run("chmod -R g+w {{cache_dir}}");
})->desc('Create cache dir');


/**
 * Normalize asset timestamps
 */
task('deploy:assets', function () {
    $assets = implode(' ', array_map(function ($asset) {
        return "{{release_path}}/$asset";
    }, get('assets')));

    run(sprintf('find %s -exec touch -t %s {} \';\' &> /dev/null || true', $assets, date('YmdHi.s')));
})->desc('Normalize asset timestamps');


/**
 * Install assets from public dir of bundles
 */
task('deploy:assets:install', function () {
    run('{{bin/php}} {{bin/console}} assets:install {{console_options}} {{release_path}}/' . get('web_dir'));
})->desc('Install bundle assets');


/**
 * Dump all assets to the filesystem
 */
task('deploy:assetic:dump', function () {
    if (get('dump_assets')) {
        run('{{bin/php}} {{bin/console}} assetic:dump {{console_options}}');
    }
})->desc('Dump assets');

/**
 * Clear Cache
 */
task('deploy:cache:clear', function () {
    run('{{bin/php}} {{bin/console}} cache:clear {{console_options}} --no-warmup');
})->desc('Clear cache');

/**
 * Warm up cache
 */
task('deploy:cache:warmup', function () {
    run('{{bin/php}} {{bin/console}} cache:warmup {{console_options}}');
})->desc('Warm up cache');


/**
 * Migrate database
 */
task('database:migrate', function () {
    run('{{bin/php}} {{bin/console}} doctrine:migrations:migrate {{console_options}} --allow-no-migration');
})->desc('Migrate database');


/**
 * Main task
 */
task('deploy', [
    'deploy:info',
    'deploy:prepare',
    'deploy:lock',
    'deploy:release',
    'deploy:update_code',
    'deploy:clear_paths',
    'deploy:create_cache_dir',
    'deploy:shared',
    'deploy:assets',
    'deploy:vendors',
    'deploy:assets:install',
    'deploy:assetic:dump',
    'deploy:cache:clear',
    'deploy:cache:warmup',
    'deploy:writable',
    'deploy:symlink',
    'deploy:unlock',
    'cleanup',
])->desc('Deploy your project');

// Display success message on completion
after('deploy', 'success');
