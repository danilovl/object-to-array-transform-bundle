# ObjectToArrayTransformBundle #

## About ##

Symfony bundle provides convert object to an array by configuration fields.

### Requirements 

  * PHP 7.3.0 or higher
  * Symfony 4.4 or higher
  * ParameterBundle 1.1.0 or higher

### 1. Installation

Install `danilovl/object-to-array-transform-bundle` package by Composer:
 
``` bash
$ composer require danilovl/object-to-array-transform-bundle
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

Each object must have declare `get` method for field defined in parameters.

Each field defined in `parameters` must match the name of the object.

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
     * @var string|null
     *
     * @ORM\Column(name="name", type="string", length=255, nullable=false)
     */
    private $name;

    /**
     * @var City|null
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\City", inversedBy="shops")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_city", referencedColumnName="id", nullable=false)
     * })
     */
    private $city;

    /**
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @return City|null
     */
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
use Doctrine\Common\Collections\{
    Collection,
    ArrayCollection
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
     * @var string|null
     *
     * @ORM\Column(name="name", type="string", nullable=false)
     */
    protected $name;

    /**
     * @return string|null
     */
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
     * @var string|null
     *
     * @ORM\Column(name="name", type="string", nullable=false)
     */
    protected $name;

    /**
     * @var string|null
     *
     * @ORM\Column(name="code", type="string", nullable=false)
     */
    protected $code;

    /**
     * @var Collection|City[]
     *
     * @ORM\OneToMany(targetEntity="App\Entity\City", mappedBy="country")
     */
    protected $cities;

    /**
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name;
    }

     /**
     * @return string|null
     */
    public function getCode(): ?string
    {
        return $this->code;
    }

    /**
     * @return Collection|City[]
     */
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
          - city:
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

If necessary to change `fields` for `cities`.

You can specify the format for `DateTime`.

```yaml
# config/services.yaml

parameters:
  api_fields:
    default:
      Shop:
        fields:
          - id
          - name
          - city:
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
              fields:
                - id
                - name
                - createdAt:
                    parameters:
                      format: 'Y-m-d'
```
   
#### 2.1 Usage

Transform objects in controller.

```php
<?php declare(strict_types=1);

namespace App\Controller\Api;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Query;
use Knp\Component\Pager\Pagination\PaginationInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

class CountryController extends AbstractController
{
 /**
     * @return JsonResponse
     * @throws ReflectionException
     */
    public function getMethod(): JsonResponse
    {
        $countries = $this->get('app.facade.country')
            ->getAll();

        $result = [];
        foreach ($countries as $country) {
            $transformer = $this->get('danilovl.object_to_array_transform')
                ->transform('api_fields.default', $country);

            array_push($result, $transformer);
        }

        return new JsonResponse($result);
    }
}
```
 