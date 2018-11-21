<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\Framework\Filesystem\Directory;

use Magento\Framework\Component\ComponentRegistrar;
use Magento\Framework\Exception\ValidatorException;
use Magento\Framework\Filesystem\DriverInterface;
use Magento\Framework\Phrase;

/**
 * @inheritDoc
 *
 * Validates paths using driver.
 */
class PathValidator implements PathValidatorInterface
{
    /**
     * @var DriverInterface
     */
    private $driver;

    /**
     * @param DriverInterface $driver
     */
    public function __construct(DriverInterface $driver)
    {
        $this->driver = $driver;
    }

    /**
     * @inheritDoc
     */
    public function validate(
        string $directoryPath,
        string $path,
        ?string $scheme = null,
        bool $absolutePath = false
    ): void {
        $realDirectoryPath = $this->driver->getRealPathSafety($directoryPath);
        if ($realDirectoryPath[-1] !== DIRECTORY_SEPARATOR) {
            $realDirectoryPath .= DIRECTORY_SEPARATOR;
        }
        if (!$absolutePath) {
            $actualPath = $this->driver->getRealPathSafety(
                $this->driver->getAbsolutePath(
                    $realDirectoryPath,
                    $path,
                    $scheme
                )
            );
        } else {
            $actualPath = $this->driver->getRealPathSafety($path);
        }

        if (mb_strpos($actualPath, $realDirectoryPath) !== 0
            && $path .DIRECTORY_SEPARATOR !== $realDirectoryPath
        ) {
            $componentsPaths = (new ComponentRegistrar())->getPaths(ComponentRegistrar::MODULE);
            foreach ($componentsPaths as $componentPath) {
                if (mb_strpos($actualPath, $componentPath) === 0) {
                    return;
                }
            }

            throw new ValidatorException(
                new Phrase(
                    'Path "%1" cannot be used with directory "%2"',
                    [$path, $directoryPath]
                )
            );
        }
    }
}
