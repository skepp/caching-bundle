# Caching Bundle

Caching for the Symfony Framework.

## What is Caching Bundle?

The caching bundle is an repository around the Symfony Cache. Its purpose is to simplify usage of Symfony Cache.

## For who?

This bundle is recommended for Microservices, Domain Driven Development or the Repository Design Pattern.
This bundle is not developed for usage with Doctrine ORM.

## Installation

Install with composer:
```
composer require batenburg/cache-bundle
```

Register the bundle, add the following line to `config/bundles.php`:
```
    Batenburg\CacheBundle\CacheBundle::class => ['all' => true],
```

## Usage

After the installation is completed, the CacheRepositoryInterface can be resolved by dependency injection.
Or through the container. It is highly recommended to use dependency injection.

An example::

    <?php
    
    namespace App\Product\Repository;
    
    use Batenburg\CacheBundle\Repository\Contract\CacheRepositoryInterface;
    use App\Product\Model\Brand;
    use App\Product\Repository\Contract\BrandRepositoryInterface;
    
    class BrandCacheRepository implements BrandRepositoryInterface
    {
    
        const EXPIRES_AFTER_IN_SECONDS = 3600;
    
        /**
         * @var CacheRepositoryInterface
         */
        private $cacheRepository;
    
        /**
         * @var BrandRepositoryInterface
         */
        private $brandRepository;
    
        /**
         * @param CacheRepositoryInterface $cacheRepository
         * @param BrandRepositoryInterface $brandRepository
         */
        public function __construct(CacheRepositoryInterface $cacheRepository, BrandRepositoryInterface $brandRepository)
        {
            $this->cacheRepository = $cacheRepository;
            $this->brandRepository = $brandRepository;
        }
    
        /**
         * @param int $id
         * @return Brand|null
         */
        public function findById(int $id): ?Brand
        {
            $item = $this->cacheRepository->rememberItem(
                "brands.{$id}",
                [$this->brandRepository, 'findById'],
                self::EXPIRES_AFTER_IN_SECONDS,
                $id
            );
    
            return $item->get();
        }
    
        /**
         * @return Brand[]
         */
        public function findAll(): array
        {
            $item = $this->cacheRepository->rememberItem(
                "brands.all",
                [$this->brandRepository, 'findAll'],
                self::EXPIRES_AFTER_IN_SECONDS
            );
    
            return $item->get();
        }
    }


## License

The Caching Bundle is open-sourced software licensed under the [MIT license](LICENSE.md).
