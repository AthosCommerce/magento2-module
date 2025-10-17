<?php
/**
 * Copyright (C) 2025 AthosCommerce <https://athoscommerce.com>
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, version 3 of the License.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace AthosCommerce\Feed\Model\Feed\Storage\File;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\WriteInterface as DirectoryWriteInterface;
use Magento\Framework\Filesystem\File\WriteInterface as FileWriteInterface;
use Magento\Framework\Math\Random;
use AthosCommerce\Feed\Model\Feed\Storage\FileInterface;

abstract class FileAbstract implements FileInterface
{
    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var DirectoryWriteInterface|null
     */
    private $directory;
    /**
     * @var Random
     */
    private $random;
    /**
     * @var string
     */
    private $subDirectory;
    /**
     * @var string
     */
    private $fileExtension;

    /**
     * @var FileWriteInterface|null
     */
    private $file;

    /**
     * @var string|null
     */
    private $path;

    /**
     * @var string|null
     */
    private $name;

    /**
     * @var bool
     */
    private $initialized = false;

    /**
     * @var bool
     */
    private $closed = true;

    /**
     * FileAbstract constructor.
     * @param Filesystem $filesystem
     * @param Random $random
     * @param string $fileExtension
     * @param string $subDirectory
     */
    public function __construct(
        Filesystem $filesystem,
        Random $random,
        string $fileExtension = 'json',
        string $subDirectory = 'athoscommerce'
    ) {
        $this->filesystem = $filesystem;
        $this->random = $random;
        $this->subDirectory = $subDirectory;
        $this->fileExtension = $fileExtension;
    }

    /**
     * @return array
     * @throws FileSystemException
     */
    public function getFileInfo(): array
    {
        if ($this->initialized) {
            return $this->getWriteDirectory()->stat($this->path);
        } else {
            return [];
        }
    }

    /**
     * @return string|null
     * @throws FileSystemException
     */
    public function getAbsolutePath(): ?string
    {
        if ($this->initialized) {
            return $this->getWriteDirectory()->getAbsolutePath($this->path);
        }

        return null;
    }

    /**
     * @throws FileSystemException
     */
    public function rollback(): void
    {
        $this->delete();
    }

    /**
     * @throws FileSystemException
     */
    public function delete(): void
    {
        if (!$this->isInitialized()) {
            return;
        }

        $this->closeFile();
        $this->getWriteDirectory()->delete($this->path);
        $this->initialized = false;
    }

    /**
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     *
     */
    public function commit(): void
    {
        if (!$this->isInitialized()) {
            return;
        }

        $this->closeFile();
    }

    /**
     * @return DirectoryWriteInterface
     * @throws FileSystemException
     */
    protected function getWriteDirectory() : DirectoryWriteInterface
    {
        if (!$this->directory) {
            $this->directory = $this->filesystem->getDirectoryWrite(DirectoryList::VAR_DIR);
        }

        return $this->directory;
    }

    /**
     * @param string $name
     * @throws FileSystemException
     * @throws LocalizedException
     */
    protected function initializeFile(string $name) : void
    {
        $this->cleanup();
        $pathWithoutExtension = $this->buildPath($name);
        $pathWithExtension = $pathWithoutExtension . '.' . $this->fileExtension;
        $directory = $this->getWriteDirectory();
        while ($directory->isExist($pathWithExtension)) {
            $name .= $this->random->getRandomString(10);
            $pathWithoutExtension = $this->buildPath($name);
            $pathWithExtension = $pathWithoutExtension . '.' . $this->fileExtension;
        }

        $file = $directory->openFile($pathWithExtension);
        $this->file = $file;
        $this->path = $pathWithExtension;
        $this->name = $name . '.' . $this->fileExtension;
        $this->initialized = true;
        $this->closed = false;
    }

    /**
     *
     * @throws \Exception
     */
    protected function checkFile() : void
    {
        if ($this->isInitialized()) {
            if (!$this->getWriteDirectory()->isFile($this->path)) {
                throw new \Exception($this->path . ' is not exist');
            }
        }
    }

    /**
     * @param string $fileName
     * @param string|null $extension
     * @return string
     */
    protected function buildPath(string $fileName, ?string $extension = null) : string
    {
        $path = $this->subDirectory . '/' . $fileName;
        if ($extension) {
            $path .= '.' . $extension;
        }

        return $path;
    }

    /**
     * @return string|null
     */
    protected function getPath() : ?string
    {
        return $this->path;
    }

    /**
     * @return FileWriteInterface|null
     */
    protected function getFile() : ?FileWriteInterface
    {
        return $this->file;
    }

    /**
     *
     */
    protected function closeFile() : void
    {
        if ($this->isInitialized() && $this->file && !$this->isClosed()) {
            $this->file->close();
            $this->closed = true;
        }
    }

    /**
     *
     */
    protected function openFile() : void
    {
        if ($this->isInitialized() && $this->path && $this->isClosed()) {
            $this->file = $this->getWriteDirectory()->openFile($this->path);
            $this->closed = false;
        }
    }

    /**
     * @return bool
     */
    protected function isClosed() : bool
    {
        return $this->closed;
    }

    /**
     * @return bool
     */
    protected function isInitialized() : bool
    {
        return $this->initialized;
    }

    /**
     *
     */
    protected function cleanup() : void
    {
        $this->directory = null;
        $this->file = null;
        $this->path = null;
        $this->name = null;
        $this->initialized = false;
        $this->closed = true;
    }
}
