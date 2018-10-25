<?php declare(strict_types=1);
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Support\Changelog;

class Changelog
{
    /**
     * @var string
     */
    private $title;

    /**
     * @var Version[]
     */
    private $versions = [];

    /**
     * @var array<int, string>
     */
    private $references = [];

    public function __toString(): string
    {
        $versions = join("\n", $this->versions);

        krsort($this->references, SORT_NUMERIC);

        $references = implode("\n", array_map(function (string $link, int $ref): string {
            return sprintf('[#%d]: %s', $ref, $link);
        }, $this->references, array_keys($this->references)));

        return <<<MD
# {$this->title}


{$versions}
{$references}

MD;
    }

    /**
     * @return void
     */
    public function setTitle(string $title)
    {
        $this->title = $title;
    }

    /**
     * @return void
     */
    public function addVersion(Version $version)
    {
        $this->versions[] = $version;
    }

    /**
     * @return void
     */
    public function prependVersion(Version $version)
    {
        array_unshift($this->versions, $version);
    }

    public function findMaster(): Version
    {
        foreach ($this->versions as $version) {
            if ($version->getVersion() === 'master') {
                return $version;
            }
        }

        $version = new Version();
        $version->setVersion('master');
        $version->setPrevious($this->findLatest()->getVersion());
        $this->prependVersion($version);

        return $version;
    }

    /**
     * @throws \RuntimeException
     */
    public function findLatest(): Version
    {
        foreach ($this->versions as $version) {
            if ($version->getVersion() === 'master') {
                continue;
            }
            return $version;
        }
        throw new \RuntimeException('There no versions.');
    }

    /**
     * @param array<int, string> $references
     *
     * @return void
     */
    public function setReferences(array $references)
    {
        $this->references = $references;
    }

    /**
     * @return void
     */
    public function addReferences(int $ref, string $url)
    {
        $this->references[$ref] = $url;
    }
}
