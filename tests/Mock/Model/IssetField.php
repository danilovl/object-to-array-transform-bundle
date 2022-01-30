<?php declare(strict_types=1);

namespace Danilovl\ObjectToArrayTransformBundle\Tests\Mock\Model;

class IssetField
{
    private array $fields = [
        'id' => null,
        'value' => null
    ];

    public function __construct(int $id, string $value)
    {
        $this->fields['id'] = $id;
        $this->fields['value'] = $value;
    }

    public function __get(string $name): mixed
    {
        if (key_exists($name, $this->fields)) {
            return $this->fields[$name];
        }

        return null;
    }

    public function __isset(string $name): bool
    {
        return key_exists($name, $this->fields);
    }
}
