[![phpunit](https://github.com/danilovl/object-to-array-transform-bundle/actions/workflows/phpunit.yml/badge.svg)](https://github.com/danilovl/object-to-array-transform-bundle/actions/workflows/phpunit.yml)
[![downloads](https://img.shields.io/packagist/dt/danilovl/object-to-array-transform-bundle)](https://packagist.org/packages/danilovl/object-to-array-transform-bundle)
[![latest Stable Version](https://img.shields.io/packagist/v/danilovl/object-to-array-transform-bundle)](https://packagist.org/packages/danilovl/object-to-array-transform-bundle)
[![license](https://img.shields.io/packagist/l/danilovl/object-to-array-transform-bundle)](https://packagist.org/packages/danilovl/object-to-array-transform-bundle)

# ObjectToArrayTransformBundle #

## About ##

Symfony bundle provides convert object to an array by configuration fields.

### Requirements

* PHP 8.5 or higher
* Symfony 8.0 or higher
* ParameterBundle 5.0 or higher

### 1. Installation

Install `danilovl/object-to-array-transform-bundle` package by Composer:

``` bash
composer require danilovl/object-to-array-transform-bundle
```
Add the `ObjectToArrayTransformBundle` to your application's bundles if does not add automatically:

``` php
<?php
// config/bundles.php

return [
    // ...
    Danilovl\ObjectToArrayTransformBundle\ObjectToArrayTransformBundle::class => ['all' => true]
];
```

### 2. Configuration

Configuration is grouped by source (for example `api_fields.default`). Inside the source each key is the short class name of the object (`Shop`, `City`) and contains the list of `fields` to export.

For each field the value is resolved in the following order:

1. method with the same name as the field (`name()`);
2. method from the field `method` parameter or the `get` method (`getName()`);
3. public property or a property available through `__get`/`__isset`.

If none of them exists, `RuntimeException` is thrown.

For example:

Shop entity.

```php
<?php declare(strict_types=1);

namespace App\Entity;

use App\Entity\Traits\{
    IdTrait,
    LocationTrait
};
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="shop")
 * @ORM\Entity(repositoryClass="App\Entity\Repository\ShopRepository")
 */
class Shop
{
    use IdTrait;
    use LocationTrait;

    /**
     * @ORM\Column(name="name", type="string", length=255, nullable=false)
     */
    private ?string $name = null;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\City", inversedBy="shops")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_city", referencedColumnName="id", nullable=false)
     * })
     */
    private ?City $city = null;

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getCity(): ?City
    {
        return $this->city;
    }
}
```

City entity.

```php
<?php declare(strict_types=1);

namespace App\Entity;

use App\Constant\TranslationConstant;
use App\Entity\Traits\{
    IdTrait,
    LocationTrait,
    TimestampAbleTrait
};
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="city")
 * @ORM\Entity(repositoryClass="App\Entity\Repository\CityRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class City
{
    use IdTrait;
    use TimestampAbleTrait;
    use LocationTrait;

    /**
     * @ORM\Column(name="name", type="string", nullable=false)
     */
    protected ?string $name = null;

    public function getName(): ?string
    {
        return $this->name;
    }
}
```

Country entity.

```php
<?php declare(strict_types=1);

namespace App\Entity;

use App\Constant\TranslationConstant;
use App\Entity\Traits\{
    IdTrait,
    TimestampAbleTrait
};
use Doctrine\Common\Collections\{
    Collection,
    ArrayCollection
};
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="country")
 * @ORM\Entity(repositoryClass="App\Entity\Repository\CountryRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class Country
{
    use IdTrait;
    use TimestampAbleTrait;

    /**
     * @ORM\Column(name="name", type="string", nullable=false)
     */
    protected ?string $name = null;

    /**
     * @ORM\Column(name="code", type="string", nullable=false)
     */
    protected ?string $code = null;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\City", mappedBy="country")
     */
    protected Collection $cities;

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function getCities(): Collection
    {
        return $this->cities;
    }
}
```

Simple configuration.

```yaml
# config/services.yaml

parameters:
  api_fields:
    default:
      Shop:
        fields:
          - id
          - name
          - city
      City:
        fields:
          - id
          - name
          - latitude
          - longitude    
      Country:
        fields:
          - id
          - name
          - code
          - cities
```

Objects whose class is configured in the source are transformed recursively with their own `fields`.

To change `fields` for a nested object or for the items of a collection, define them directly on the field.

You can specify the format for `DateTimeInterface` values.

```yaml
# config/services.yaml

parameters:
  api_fields:
    default:
      parameters:
        date_format: 'Y-m-d H:i:s'

      Shop:
        fields:
          - id
          - name
          - city
          - active:
              parameters:
                method: 'isActive'
      City:
        fields:
          - id
          - name
          - latitude
          - longitude
      Country:
        fields:
          - id
          - name
          - code
          - cities:
              fields:
                - id
                - name
                - createdAt:
                    parameters:
                      format: 'Y-m-d'
```

#### 2.1 Transformation rules

* **Date format** for `DateTimeInterface` values is chosen in the following order:
    1. field parameter `format`;
    2. source parameter `date_format`;
    3. default `Y-m-d H:i:s`.
* **Collections** (any `Traversable`, for example Doctrine `Collection`) are transformed to a list:
    * an empty collection is returned as an empty array `[]`;
    * objects are transformed by their configuration;
    * scalar values are returned as is.
* **Circular references** (for example `Product → Category → products → Product`) throw `RuntimeException` with message `Circular reference detected for class "..."`. The same object in different branches (for example twice in one collection) is not a circular reference.
* **Maximum depth** of nested objects is `32`. Deeper nesting throws `RuntimeException`.
* If the object is passed as a class name string and the class does not exist, `RuntimeException` is thrown.

#### 2.2 Usage

Transform objects in controller.

```php
<?php declare(strict_types=1);

namespace App\Controller\Api;

use App\Facade\CountryFacade;
use Danilovl\ObjectToArrayTransformBundle\Interfaces\ObjectToArrayTransformServiceInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;

class CountryController extends AbstractController
{
    public function __construct(
        private readonly CountryFacade $countryFacade,
        private readonly ObjectToArrayTransformServiceInterface $objectToArrayTransformService
    ) {}

    public function getMethod(): JsonResponse
    {
        $countries = $this->countryFacade->getAll();

        $result = [];
        foreach ($countries as $country) {
            $transformer = $this->objectToArrayTransformService->transform('api_fields.default', $country);

            $result[] = $transformer;
        }

        return new JsonResponse($result);
    }
}
```

## License

The ObjectToArrayTransformBundle is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
