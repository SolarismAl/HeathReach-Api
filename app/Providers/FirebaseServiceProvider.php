<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\FirebaseService;
use App\Services\FirestoreService;
use App\Services\ActivityLogService;
use Google\Cloud\Firestore\FirestoreClient;

class FirebaseServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Bind Google Cloud Firestore Client
        $this->app->singleton(FirestoreClient::class, function ($app) {
            return new FirestoreClient([
                'projectId' => config('firebase.project_id'),
                'keyFile'   => config('firebase.credentials') // Already an array
            ]);
        });

        $this->app->singleton(FirebaseService::class, function ($app) {
            return new FirebaseService();
        });

        $this->app->singleton(FirestoreService::class, function ($app) {
            return new FirestoreService();
        });

        $this->app->singleton(ActivityLogService::class, function ($app) {
            return new ActivityLogService($app->make(FirestoreService::class));
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
