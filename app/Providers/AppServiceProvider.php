<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Repositories\UserRepositoryInterface;
use App\Repositories\PermitRepositoryInterface;
use App\Repositories\CommentRepositoryInterface;
use App\Repositories\CitizenCharterRepositoryInterface;
use App\Repositories\ViolationRepositoryInterface;
use App\Repositories\Implementations\UserRepository;
use App\Repositories\Implementations\PermitRepository;
use App\Repositories\Implementations\CommentRepository;
use App\Repositories\Implementations\CitizenCharterRepository;
use App\Repositories\Implementations\ViolationRepository;
class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
       $this->app->bind(UserRepositoryInterface::class, UserRepository::class);
       $this->app->bind(PermitRepositoryInterface::class, PermitRepository::class);
       $this->app->bind(CommentRepositoryInterface::class, CommentRepository::class);
       $this->app->bind(CitizenCharterRepositoryInterface::class, CitizenCharterRepository::class);
       $this->app->bind(ViolationRepositoryInterface::class, ViolationRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {


    }
}
