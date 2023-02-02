<?php declare(strict_types=1);

namespace Danilovl\ObjectToArrayTransformBundle\Service;

use Danilovl\ObjectToArrayTransformBundle\Interfaces\ObjectToArrayTransformServiceInterface;
use Danilovl\ParameterBundle\Interfaces\ParameterServiceInterface;
use DateTime;
use ReflectionClass;
use RuntimeException;
use Traversable;

readonly class ObjectToArrayTransformService implements ObjectToArrayTransformServiceInterface
{
    private const DEFAULT_DATE_FORMAT = 'Y-m-d H:i:s';

    public function __construct(private ParameterServiceInterface $parameterService) {}

    public function transform(
        string $source,
        string|object $object,
        array $objectFields = null
    ): array {
        $result = [];
        $fieldValueClass = (new ReflectionClass($object))->getShortName();

        $sourceParameters = $this->parameterService->get(key: "{$source}.parameters", ignoreNotFound: true) ?? [];
        $objectFields = $objectFields ?? $this->parameterService->get(key: "{$source}.{$fieldValueClass}.fields");

        if ($objectFields === null) {
            throw new RuntimeException(sprintf('Object fields for class "%s" is not defined for transformation.', $fieldValueClass));
        }

        $objectsName = $this->parameterService->get($source);

        foreach ($objectFields as $objectField) {
            $field = $objectField;
            $fieldParameters = null;

            $subFields = null;
            if (is_array($objectField)) {
                $field = array_key_first($objectField);
                $subFields = $objectField[$field]['fields'] ?? null;
                $fieldParameters = $objectField[$field]['parameters'] ?? null;
            }

            $objectFieldsValue = get_class_vars(is_object($object) ? get_class($object) : $object);
            $objectFields = array_keys($objectFieldsValue);

            $isFieldExist = false;
            $fieldValue = null;
            $getMethod = $fieldParameters['method'] ?? 'get' . ucfirst($field);

            if (method_exists($object, $field)) {
                $fieldValue = call_user_func_array([$object, $field], []);
                $isFieldExist = true;
            } elseif (method_exists($object, $getMethod)) {
                $fieldValue = call_user_func_array([$object, $getMethod], []);
                $isFieldExist = true;
            } elseif (in_array($field, $objectFields, true) || isset($object->{$field})) {
                $fieldValue = $object->{$field};
                $isFieldExist = true;
            }

            if ($isFieldExist === false) {
                throw new RuntimeException(sprintf('Can not find public field or get method for field "%s".', $field));
            }

            $fieldValueClass = null;
            if (is_object($fieldValue)) {
                $fieldValueClass = (new ReflectionClass($fieldValue))->getShortName();
            }

            if ($fieldValueClass !== null && isset($objectsName[$fieldValueClass])) {
                $result[$field] = $this->transform($source, $fieldValue, $subFields);
            } elseif ($fieldValue instanceof Traversable) {
                foreach ($fieldValue as $itemV) {
                    $result[$field][] = $this->transform($source, $itemV, $subFields);
                }
            } else {
                if ($fieldValue instanceof DateTime) {
                    $dateFormat = $sourceParameters['date_format'] ?? null;
                    $dateFormat = $dateFormat ?? $fieldParameters['format'] ?? self::DEFAULT_DATE_FORMAT;

                    $fieldValue = $fieldValue->format($dateFormat);
                }

                $result[$field] = $fieldValue;
            }
        }

        return $result;
    }
}
