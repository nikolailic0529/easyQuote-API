<?php

namespace App\Foundation\Validation\Providers;

use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ServiceProvider;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ValidationServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(
            ValidatorInterface::class,
            function () {
                return Validation::createValidatorBuilder()
                    ->enableAnnotationMapping(true)
                    ->addDefaultDoctrineAnnotationReader()
                    ->getValidator();
            }
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Validator::extend('phone', function ($attribute, $value, $parameters) {
            return preg_match('/\+?[\d]+/', $value);
        }, 'Phone must contain only digits.');

        Validator::extend('alpha_spaces', function ($attribute, $value, $parameters) {
            return preg_match('/^[\pL\s]+$/', $value);
        }, 'The :attribute may only contain letters and spaces.');

        Validator::extend('one_of', function (...$args) {
            dd($args);
        });
    }
}
