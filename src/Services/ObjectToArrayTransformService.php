<?php declare(strict_types=1);

namespace Danilovl\ObjectToArrayTransformBundle\Services;

use Danilovl\ParameterBundle\Services\ParameterService;
use DateTime;
use ReflectionClass;
use ReflectionException;
use RuntimeException;
use Traversable;

class ObjectToArrayTransformService
{
    /**
     * @var ParameterService
     */
    private $parameterService;

    /**
     * @param ParameterService $parametersService
     */
    public function __construct(ParameterService $parametersService)
    {
        $this->parameterService = $parametersService;
    }

    /**
     * @param string $source
     * @param $object
     * @param array|null $objectFields
     * @return array
     * @throws ReflectionException
     */
    public function transform(
        string $source,
        $object,
        array $objectFields = null
    ): array {
        $result = [];

        $fieldValueClass = (new ReflectionClass($object))->getShortName();
        $objectFields = $objectFields ?? $this->parameterService
                ->get("{$source}.{$fieldValueClass}.fields");

        if ($objectFields === null) {
            throw new RuntimeException(sprintf('Object "%s" is not defined for transformation.', $fieldValueClass));
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

            $getMethod = 'get' . ucfirst($field);

            if (method_exists($object, $getMethod)) {
                $fieldValue = call_user_func_array([$object, $getMethod], []);

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
                        $fieldValue = $fieldValue->format($fieldParameters['format']);
                    }

                    $result[$field] = $fieldValue;
                }
            }
        }

        return $result;
    }
}
