# Laravel trustup media io

## Compatibility

| Laravel | Package |
|---|---|
| 8.x | 1.x |
| 12.x | 2.x |

## Installation

### Require package
``` shell
composer require henrotaym/laravel-trustup-media-io
```

### Set environment variables
``` dotenv
TRUSTUP_MEDIA_IO_URL=
TRUSTUP_APP_KEY=

TRUSTUP_SERVER_AUTHORIZATION=
```

### Prepare your models
Here is a example where a post is having a single cover and multiple images.
``` php
<?php
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Henrotaym\LaravelTrustupMediaIo\Models\Traits\HasTrustupMedia;
use Henrotaym\LaravelTrustupMediaIo\Contracts\Models\MediaContract;
use Henrotaym\LaravelTrustupMediaIoCommon\Enums\Media\MediaCollections;
use Henrotaym\LaravelTrustupMediaIo\Contracts\Models\HasTrustupMediaContract;
use Deegitalbe\LaravelTrustupIoExternalModelRelations\Contracts\Models\Relations\ExternalModelRelationContract;

class Post implements HasTrustupMediaContract
{
    use HasTrustupMedia;

    public function getExternalRelationNames(): array
    {
        return [
            'cover',
            'images'
        ];
    }

    /**
     * Cover relation.
     * 
     * @return ExternalModelRelationContract
     */
    public function cover(): ExternalModelRelationContract
    {
        return $this->hasOneTrustupMedia('cover_id');
    }

    /**
     * Images relation
     * 
     * @return ExternalModelRelationContract
     */
    public function images(): ExternalModelRelationContract
    {
        return $this->hasManyTrustupMedia('image_ids');
    }

    /**
     * Getting related images.
     * 
     * @return Collection<int, MediaContract>
     */
    public function getImages(): Collection
    {
        return $this->getExternalModels('images');
    }

    /**
     * Getting related cover.
     * 
     * @return ?MediaContract
     */
    public function getCover(): ?MediaContract
    {
        return $this->getExternalModels('cover');
    }

    /**
     * Setting cover.
     * 
     * @param string|UploadedFile $resource
     * @return static
     */
    public function setCover(string|UploadedFile $resource): self
    {
        // Removing old cover if any.
        $this->removeCover();

        $response = $this->addTrustupMediaFromResource($resource, MediaCollections::IMAGES);

        if (!$response->ok()) return $this;

        $this->cover()->setRelatedModels($response->getFirstMedia());

        return $this;
    }

    /**
     * Removing current cover.
     * 
     * @return static
     */
    public function removeCover(): self
    {
        if (!$this->cover_id) return $this;

        $response = $this->deleteTrustupMediaByUuidCollection(collect($this->cover_id));

        if (!$response->ok()) return $this;

        $this->cover()->setRelatedModels(null);

        return $this;
    }

    /**
     * Adding given image
     * 
     * @param string|UploadedFile $resource
     * @return static
     */
    public function addImage(string|UploadedFile $resource): self
    {
        $response = $this->addTrustupMediaFromResource($resource, MediaCollections::IMAGES);

        if (!$response->ok()) return $this;

        $this->images()->addToRelatedModels($response->getFirstMedia());

        return $this;
    }

    /**
     * Adding given images.
     * 
     * @param Collection<int, string|UploadedFile>
     * @return static
     */
    public function addImages(Collection $resources): self
    {
        $response = $this->addTrustupMediaFromResourceCollection($resources, MediaCollections::IMAGES);

        if (!$response->ok()) return $this;

        $this->images()->addToRelatedModels($response->getMedia());

        return $this;
    }

    /**
     * Removing current images.
     * 
     * @return static
     */
    public function removeImages(): self
    {
        if ($this->image_ids) return $this;

        $response = $this->deleteTrustupMediaByUuidCollection($this->image_ids);

        if (!$response->ok()) return $this;

        $this->images()->setRelatedModels(null);

        return $this;
    }
}
```

### Expose your models (using a resource)
Your resource should look like this.
```php
use Deegitalbe\LaravelTrustupIoExternalModelRelations\Traits\Resources\IsExternalModelRelatedResource;
use Henrotaym\LaravelTrustupMediaIo\Resources\Models\Media;
use Illuminate\Http\Resources\Json\JsonResource;

class PostResource extends JsonResource
{
    use IsExternalModelRelatedResource;

    public function toArray($request)
    {
        return [
            'cover' => new Media($this->whenExternalRelationLoaded('cover')),
            'images' => Media::collection($this->whenExternalRelationLoaded('images'))
        ];
    }
}
```

### Eager loading your relations
Even if you load several relations, only one request will be performed ⚡
```php
use Illuminate\Routing\Controller;
use App\Models\Post;
use App\Http\Resources\PostResource;

class PostController extends Controller
{
    public function index()
    {
        $posts = Post::all()->loadExternalRelations('cover', 'images');

        return PostResource::collection($posts);
    }
}
```

### Getting related models
If your relation is not eager loaded, it will be loaded when using model getter (n+1 requests tho...)
```php
use Illuminate\Routing\Controller;
use App\Models\Post;
use App\Http\Resources\PostResource;

class PostController extends Controller
{
    public function index()
    {
        $post = Post::first();

        $post->getCover() // ?MediaContract
        $post->getImages() // Collection<int, MediaContract>
    }
}
```
