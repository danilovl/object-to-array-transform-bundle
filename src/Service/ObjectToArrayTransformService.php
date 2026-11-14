<?php declare(strict_types=1);

namespace Danilovl\ObjectToArrayTransformBundle\Service;

use Danilovl\ObjectToArrayTransformBundle\Interfaces\ObjectToArrayTransformServiceInterface;
use Danilovl\ParameterBundle\Interfaces\ParameterServiceInterface;
use DateTimeInterface;
use ReflectionClass;
use RuntimeException;
use Traversable;

readonly class ObjectToArrayTransformService implements ObjectToArrayTransformServiceInterface
{
    private const string DEFAULT_DATE_FORMAT = 'Y-m-d H:i:s';

    private const int MAX_DEPTH = 32;

    public function __construct(private ParameterServiceInterface $parameterService) {}

    public function transform(
        string $source,
        string|object $object,
        ?array $objectFields = null
    ): array {
        return $this->transformObject($source, $object, $objectFields, []);
    }

    /**
     * @param array<int, true> $parentObjectIds
     */
    private function transformObject(
        string $source,
        string|object $object,
        ?array $objectFields,
        array $parentObjectIds
    ): array {
        if (count($parentObjectIds) >= self::MAX_DEPTH) {
            $message = sprintf('Maximum transformation depth %d exceeded.', self::MAX_DEPTH);

            throw new RuntimeException($message);
        }

        if (is_string($object) && !class_exists($object)) {
            $message = sprintf('Class "%s" does not exist.', $object);

            throw new RuntimeException($message);
        }

        if (is_object($object)) {
            $objectId = spl_object_id($object);
            if (isset($parentObjectIds[$objectId])) {
                $message = sprintf('Circular reference detected for class "%s".', $object::class);

                throw new RuntimeException($message);
            }

            $parentObjectIds[$objectId] = true;
        }

        $result = [];
        $fieldValueClass = (new ReflectionClass($object))->getShortName();

        $sourceParameters = $this->parameterService->getArrayOrNull(key: "{$source}.parameters");
        $objectFields ??= $this->parameterService->getArrayOrNull(key: "{$source}.{$fieldValueClass}.fields");

        if ($objectFields === null) {
            $message = sprintf('Object fields for class "%s" is not defined for transformation.', $fieldValueClass);

            throw new RuntimeException($message);
        }

        $objectsName = $this->parameterService->getArray($source);

        foreach ($objectFields as $objectField) {
            $field = $objectField;
            $fieldParameters = null;

            $subFields = null;
            if (is_array($objectField)) {
                /** @var array<string, array<string, array>> $objectField */
                /** @var string $field */
                $field = array_key_first($objectField);
                $subFields = $objectField[$field]['fields'] ?? null;
                $fieldParameters = $objectField[$field]['parameters'] ?? null;
            }

            $classVars = get_class_vars(is_object($object) ? get_class($object) : $object);
            $classFields = array_keys($classVars);

            $isFieldExist = false;
            $fieldValue = null;
            $getMethod = $fieldParameters['method'] ?? 'get' . ucfirst($field);

            if (method_exists($object, $field)) {
                /** @var callable $callable */
                $callable = [$object, $field];
                $fieldValue = call_user_func_array($callable, []);
                $isFieldExist = true;
            } elseif (method_exists($object, $getMethod)) {
                /** @var callable $callable */
                $callable = [$object, $getMethod];
                $fieldValue = call_user_func_array($callable, []);
                $isFieldExist = true;
            } elseif (in_array($field, $classFields, true) || isset($object->{$field})) {
                $fieldValue = $object->{$field};
                $isFieldExist = true;
            }

            if ($isFieldExist === false) {
                $message = sprintf('Can not find public field or get method for field "%s".', $field);

                throw new RuntimeException($message);
            }

            $fieldValueClass = null;
            if (is_object($fieldValue)) {
                $fieldValueClass = (new ReflectionClass($fieldValue))->getShortName();
            }

            if ($fieldValueClass !== null && isset($objectsName[$fieldValueClass])) {
                $result[$field] = $this->transformObject($source, $fieldValue, $subFields, $parentObjectIds);
            } elseif ($fieldValue instanceof Traversable) {
                $result[$field] = [];
                foreach ($fieldValue as $itemValue) {
                    $result[$field][] = is_object($itemValue)
                        ? $this->transformObject($source, $itemValue, $subFields, $parentObjectIds)
                        : $itemValue;
                }
            } else {
                if ($fieldValue instanceof DateTimeInterface) {
                    $dateFormat = $fieldParameters['format']
                        ?? $sourceParameters['date_format']
                        ?? self::DEFAULT_DATE_FORMAT;

                    $fieldValue = $fieldValue->format($dateFormat);
                }

                $result[$field] = $fieldValue;
            }
        }

        return $result;
    }
}
