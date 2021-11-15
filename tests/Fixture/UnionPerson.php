<?php

declare(strict_types=1);

namespace Symplify\EasyHydrator\Tests\Fixture;

final class UnionPerson
{
    private Person | PersonWithAge $person;

    public function __construct(Person | PersonWithAge $person)
    {
        $this->person = $person;
    }

    public function getPerson(): Person | PersonWithAge
    {
        return $this->person;
    }
}
