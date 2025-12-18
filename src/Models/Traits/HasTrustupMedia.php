<?php

namespace Henrotaym\LaravelTrustupMediaIo\Models\Traits;

use Deegitalbe\LaravelTrustupIoExternalModelRelations\Contracts\Models\Relations\ExternalModelRelationContract;
use Deegitalbe\LaravelTrustupIoExternalModelRelations\Traits\Models\IsExternalModelRelatedModel;
use Henrotaym\LaravelTrustupMediaIo\Contracts\Endpoints\MediaEndpointContract;
use Henrotaym\LaravelTrustupMediaIo\Contracts\Responses\Media\DestroyMediaResponseContract;
use Henrotaym\LaravelTrustupMediaIo\Contracts\Responses\Media\GetMediaResponseContract;
use Henrotaym\LaravelTrustupMediaIo\Contracts\Responses\Media\StoreMediaResponseContract;
use Henrotaym\LaravelTrustupMediaIo\Models\MediaRelationLoadingCallback;
use Henrotaym\LaravelTrustupMediaIoCommon\Contracts\Requests\Media\_Private\MediaRequestContract;
use Henrotaym\LaravelTrustupMediaIoCommon\Contracts\Requests\Media\DestroyMediaRequestContract;
use Henrotaym\LaravelTrustupMediaIoCommon\Contracts\Requests\Media\GetMediaRequestContract;
use Henrotaym\LaravelTrustupMediaIoCommon\Contracts\Requests\Media\StoreMediaRequestContract;
use Henrotaym\LaravelTrustupMediaIoCommon\Contracts\Transformers\Models\StorableMediaTransformerContract;
use Henrotaym\LaravelTrustupMediaIoCommon\Enums\Media\MediaCollections;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Psr\Http\Message\StreamInterface;

/**
 * @var Model $this
 */
trait HasTrustupMedia
{
    use IsExternalModelRelatedModel;

    /**
     * Getting model identifier for media.trustup.io
     */
    public function getTrustupMediaModelId(): string
    {
        /** @var Model $this */
        return $this->uuid ??
            $this->id;
    }

    /**
     * Getting model type for media.trustup.io
     */
    public function getTrustupMediaModelType(): string
    {
        /** @var Model $this */
        return Str::slug(str_replace('\\', '-', $this->getMorphClass()));
    }

    /**
     * Adding trustup media using a customized request.
     */
    public function addTrustupMedia(StoreMediaRequestContract $request): StoreMediaResponseContract
    {
        $this->prepareTrustupMediaRequest($request);

        /** @var MediaEndpointContract */
        $endpoint = app()->make(MediaEndpointContract::class);

        return $endpoint->store($request);
    }

    /**
     * Adding trustup media from a single resource.
     */
    public function addTrustupMediaFromResource(
        string|UploadedFile|StreamInterface $resource,
        string|MediaCollections|null $collection,
        bool $isUsingQueue = false
    ): StoreMediaResponseContract {
        return $this->addTrustupMediaFromAttributes(
            resource: $resource,
            collection: $collection,
            isUsingQueue: $isUsingQueue,
        );
    }

    /**
     * Adding trustup media from a resources collection.
     *
     * @param Collection<int, string|UploadedFile|StreamInterface> $resourceCollection */
    public function addTrustupMediaFromResourceCollection(
        Collection $resourceCollection,
        string|MediaCollections|null $collection,
        bool $isUsingQueue = false
    ): StoreMediaResponseContract {
        $attributesCollection = $resourceCollection->map(fn ($resource) => [
            'resource' => $resource,
            'collection' => $collection,
        ]);

        return $this->addTrustupMediaFromAttributesCollection($attributesCollection, $isUsingQueue);
    }

    /**
     * array{
     *  resource: string|UploadedFile|StreamInterface,
     *  name?: string,
     *  custom_properties?: array,
     *  collection?: string|MediaCollections
     * }> $attributes
     */
    public function addTrustupMediaFromAttributes(
        string|UploadedFile|StreamInterface $resource,
        string|MediaCollections|null $collection = null,
        ?string $filename = null,
        ?array $customProperties = null,
        bool $isUsingQueue = false
    ) {
        $attributes = [
            'resource' => $resource,
            'collection' => $collection,
            'name' => $filename,
            'custom_properties' => $customProperties,
        ];

        return $this->addTrustupMediaFromAttributesCollection([$attributes], $isUsingQueue);
    }

    /**
     * @param iterable<int, array{
     *  resource: string|UploadedFile|StreamInterface,
     *  name?: string,
     *  custom_properties?: array,
     *  collection?: string|MediaCollections
     * }> $attributesCollection
     */
    public function addTrustupMediaFromAttributesCollection(
        iterable $attributesCollection,
        bool $isUsingQueue = false
    ) {
        /** @var StorableMediaTransformerContract */
        $transformer = app()->make(StorableMediaTransformerContract::class);
        /** @var StoreMediaRequestContract */
        $request = app()->make(StoreMediaRequestContract::class);

        foreach ($attributesCollection as $attributes) {
            $request->addMedia($transformer->fromArray($attributes));
        }

        $request->useQueue($isUsingQueue);

        return $this->addTrustupMedia($request);
    }

    /**
     * Retrieving trustup media using a customized request.
     */
    public function getTrustupMedia(GetMediaRequestContract $request): GetMediaResponseContract
    {
        $this->prepareTrustupMediaRequest($request);

        $request->setExpectedWidth($request->getExpectedWidth() ?: request()->input('expected_width'))
            ->setExpectedHeight($request->getExpectedHeight() ?: request()->input('expected_height'));

        /** @var MediaEndpointContract */
        $endpoint = app()->make(MediaEndpointContract::class);

        return $endpoint->get($request);
    }

    /**
     * Retrieving trustup media linked to given collection.
     */
    public function getTrustupMediaCollection(string|MediaCollections $mediaCollection, bool $firstOnly = false): GetMediaResponseContract
    {
        /** @var GetMediaRequestContract */
        $request = app()->make(GetMediaRequestContract::class);

        $mediaCollection instanceof MediaCollections ?
            $request->setMediaCollection($mediaCollection)
            : $request->setCollection($mediaCollection);

        $request->firstOnly($firstOnly);

        return $this->getTrustupMedia($request);
    }

    /**
     * Deleting trustup media using a customized request.
     */
    public function deleteTrustupMedia(DestroyMediaRequestContract $request): DestroyMediaResponseContract
    {
        $this->prepareTrustupMediaRequest($request);

        /** @var MediaEndpointContract */
        $endpoint = app()->make(MediaEndpointContract::class);

        return $endpoint->destroy($request);
    }

    /**
     * Deleting trustup media matching given uuids collection.
     */
    public function deleteTrustupMediaByUuidCollection(Collection $mediaUuidCollection): DestroyMediaResponseContract
    {
        /** @var DestroyMediaRequestContract */
        $request = app()->make(DestroyMediaRequestContract::class);

        $request->addUuidCollection($mediaUuidCollection);

        return $this->deleteTrustupMedia($request);
    }

    /**
     * Deleting trustup media linked to given collection.
     */
    public function deleteTrustupMediaCollection(string|MediaCollections $mediaCollection): DestroyMediaResponseContract
    {
        /** @var DestroyMediaRequestContract */
        $request = app()->make(DestroyMediaRequestContract::class);

        is_string($mediaCollection) ?
            $request->setCollection($mediaCollection)
            : $request->setMediaCollection($mediaCollection);

        return $this->deleteTrustupMedia($request);
    }

    protected function prepareTrustupMediaRequest(MediaRequestContract &$request)
    {
        $request->setModelId($this->getTrustupMediaModelId())
            ->setModelType($this->getTrustupMediaModelType());
    }

    /**
     * Media relation based on stored media identifier.
     */
    public function hasOneTrustupMedia(string $idProperty, ?string $name = null): ExternalModelRelationContract
    {
        return $this->belongsToExternalModel(
            app()->make(MediaRelationLoadingCallback::class),
            $idProperty,
            $name
        );
    }

    /**
     * Media relation based on stored media identifiers.
     */
    public function hasManyTrustupMedia(string $idsProperty, ?string $name = null): ExternalModelRelationContract
    {
        return $this->hasManyExternalModels(
            app()->make(MediaRelationLoadingCallback::class),
            $idsProperty,
            $name
        );
    }
}
