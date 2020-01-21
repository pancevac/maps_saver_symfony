<?php


namespace App\Utils;


interface MassSavingInterface
{
    /**
     * Hydrate entity properties from array. Array keys must correspond
     * to property name.
     *
     * @example [name => 'value'], where "name" is property
     *
     * @param array $data
     * @return $this
     */
    public function hydrate(array $data): self;
}