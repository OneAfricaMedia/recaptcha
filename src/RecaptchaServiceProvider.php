<?php namespace Greggilbert\Recaptcha;

use Illuminate\Support\ServiceProvider;

/**
 * Service provider for the Recaptcha class
 *
 * @author     Greg Gilbert
 * @link       https://github.com/greggilbert
 */
class RecaptchaServiceProvider extends ServiceProvider
{

    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        $this->loadViewsFrom(__DIR__ . '/views', 'recaptcha');
    }

    /**
     * Extends Validator to include a recaptcha type
     */
    public function addValidator()
    {
        $this->app->resolving(
            'validator',
            function ($validator) {
                $validator->extendImplicit(
                    'recaptcha',
                    function ($attribute, $value, $parameters) {
                        $captcha = app('recaptcha.service');
                        $challenge = app('request')->input($captcha->getResponseKey());

                        return $captcha->check($challenge, $value);
                    },
                    'Please ensure that you are a human!'
                );
            }
        );
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->handleConfig();
        $this->bindRecaptcha();
        $this->addValidator();
    }

    protected function bindRecaptcha()
    {
        $this->app->bind('recaptcha.service', function () {
            if (
                app('config')->get('recaptcha.version') === 'invisible'
            ) {
                return new Service\CheckRecaptchaInvisible;
            }

            if (
                app('config')->get('recaptcha.version') === 2
                || app('config')->get('recaptcha.v2')
            ) {
                return new Service\CheckRecaptchaV2;
            }

            return new Service\CheckRecaptcha;
        });

        $this->app->bind('recaptcha', function () {
            return new Recaptcha($this->app->make('recaptcha.service'), app('config')->get('recaptcha'));
        });

    }

    protected function handleConfig()
    {
        $packageConfig = __DIR__ . '/config/recaptcha.php';
        $destinationConfig = config_path('recaptcha.php');

        $this->publishes([
            $packageConfig => $destinationConfig,
        ]);

        $this->mergeConfigFrom(
            $packageConfig,
            'recaptcha'
        );
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [
            'recaptcha',
        ];
    }
}
