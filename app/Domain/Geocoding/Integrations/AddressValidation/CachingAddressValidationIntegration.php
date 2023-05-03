<?php

namespace App\Domain\Geocoding\Integrations\AddressValidation;

use App\Domain\Geocoding\Integrations\AddressValidation\Models\ValidateAddressRequest;
use App\Domain\Geocoding\Integrations\AddressValidation\Models\ValidationResponse;
use Illuminate\Contracts\Cache\Repository as Cache;
use Symfony\Component\Serializer\Encoder\JsonEncode;
use Symfony\Component\Serializer\Normalizer\BackedEnumNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

class CachingAddressValidationIntegration implements ValidatesAddress
{
    protected Serializer $serializer;

    public function __construct(
        protected readonly AddressValidationIntegration $delegate,
        protected readonly Cache $cache,
    ) {
        $normalizers = [new BackedEnumNormalizer(), new ObjectNormalizer()];
        $encoders = [new JsonEncode()];

        $this->serializer = new Serializer($normalizers, $encoders);
    }

    public function validateAddress(ValidateAddressRequest $request): ValidationResponse
    {
        $key = $this->getRequestCacheKey($request);

        $delegate = $this->delegate;

        return $this->cache->rememberForever($key, static function () use ($delegate, $request): ValidationResponse {
            return $delegate->validateAddress($request);
        });
    }

    protected function getRequestCacheKey(ValidateAddressRequest $request): string
    {
        $hash = sha1($this->serializer->serialize($request, 'json'));

        return static::class.$hash;
    }
}
