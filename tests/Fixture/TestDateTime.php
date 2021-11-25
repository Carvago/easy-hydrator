<?php

declare(strict_types=1);

namespace Symplify\EasyHydrator\Tests\Fixture;

use DateTime;
use DateTimeImmutable;
use DateTimeInterface;

final class TestDateTime
{
    public function __construct(
        public DateTimeInterface $value1,
        public DateTime $value2,
        public DateTimeImmutable $value3,
    ) {
    }
}
