<?php

declare(strict_types=1);

namespace Symplify\EasyHydrator\Tests\HttpKernel;

use Psr\Container\ContainerInterface;
use Symplify\SymplifyKernel\HttpKernel\AbstractSymplifyKernel;

final class EasyHydratorTestKernel extends AbstractSymplifyKernel
{
    /**
     * @inheritdoc
     */
    public function createFromConfigs(array $configFiles): ContainerInterface
    {
        $configFiles[] = __DIR__ . '/../../config/config.php';
        return $this->create([], [], $configFiles);
    }
}
