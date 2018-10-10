<?php declare(strict_types=1);
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Initializer\Template;

/**
 * Generate a Symfony deployer configuration.
 *
 * @author Anton Medvedev <anton@medv.io>
 * @codeCoverageIgnore
 */
class SymfonyTemplate extends FrameworkTemplate
{
    protected function getRecipe(): string
    {
        return 'symfony';
    }

    /**
     * {@inheritDoc}
     */
    protected function getExtraContent(): string
    {
        return <<<PHP

// Migrate database before symlink new release.

before('deploy:symlink', 'database:migrate');

PHP;
    }
}
