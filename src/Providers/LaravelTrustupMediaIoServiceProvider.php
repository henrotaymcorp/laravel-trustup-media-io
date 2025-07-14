<?php
namespace Henrotaym\LaravelTrustupMediaIo\Providers;

use Henrotaym\LaravelApiClient\Client;
use Henrotaym\LaravelTrustupMediaIo\Package;
use Henrotaym\LaravelTrustupMediaIo\Models\Media;
use Henrotaym\LaravelApiClient\Contracts\ClientContract;
use Henrotaym\LaravelTrustupMediaIo\Endpoints\MediaEndpoint;
use Henrotaym\LaravelTrustupMediaIo\Contracts\Models\MediaContract;
use Henrotaym\LaravelTrustupMediaIo\Responses\Media\GetMediaResponse;
use Henrotaym\LaravelTrustupMediaIo\Credentials\Media\MediaCredential;
use Henrotaym\LaravelTrustupMediaIo\Responses\Media\StoreMediaResponse;
use Henrotaym\LaravelTrustupMediaIo\Transformers\Models\MediaTransformer;
use Henrotaym\LaravelTrustupMediaIo\Contracts\Endpoints\MediaEndpointContract;
use Henrotaym\LaravelTrustupMediaIo\Contracts\Responses\Media\GetMediaResponseContract;
use Henrotaym\LaravelTrustupMediaIo\Contracts\Responses\Media\StoreMediaResponseContract;
use Henrotaym\LaravelTrustupMediaIo\Contracts\Transformers\Models\MediaTransformerContract;
use Henrotaym\LaravelPackageVersioning\Providers\Abstracts\VersionablePackageServiceProvider;
use Henrotaym\LaravelTrustupMediaIo\Contracts\Responses\Media\DestroyMediaResponseContract;
use Henrotaym\LaravelTrustupMediaIo\Responses\Media\DestroyMediaResponse;
use Illuminate\Database\Eloquent\Relations\Relation;

class LaravelTrustupMediaIoServiceProvider extends VersionablePackageServiceProvider
{
    public static function getPackageClass(): string
    {
        return Package::class;
    }

    protected function addToRegister(): void
    {
        $this->registerMediaEndpoint();

        $this->app->bind(MediaEndpointContract::class, MediaEndpoint::class);
        $this->app->bind(MediaContract::class, Media::class);
        $this->app->bind(GetMediaResponseContract::class, GetMediaResponse::class);
        $this->app->bind(StoreMediaResponseContract::class, StoreMediaResponse::class);
        $this->app->bind(MediaTransformerContract::class, MediaTransformer::class);
        $this->app->bind(DestroyMediaResponseContract::class, DestroyMediaResponse::class);

        $this->mergeConfigFrom(
        __DIR__ . '/../config/laravel-trustup-media-io.php', 'laravel-trustup-media-io'
    );
    }

    protected function registerMediaEndpoint(): self
    {
        $this->app->when(MediaEndpoint::class)
            ->needs(ClientContract::class)
            ->give(fn ($app) => $app->make(Client::class, ['credential' => new MediaCredential]));

        return $this;
    }

    protected function addToBoot(): void
    {
        Relation::requireMorphMap();

        $this->publishes([
        __DIR__ . '/../config/laravel-trustup-media-io.php' => config_path('laravel-trustup-media-io.php'),
        ], 'laravel-trustup-media-io-config');
    }
}