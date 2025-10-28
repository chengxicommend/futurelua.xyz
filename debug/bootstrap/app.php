<?php

use App\Domain\User;
use App\Http\Utils;
use Illuminate\Database\Capsule\Manager;
use Slim\App;
use Slim\Views\Twig;
use Symfony\Bridge\Twig\Extension\TranslationExtension;
use Symfony\Component\Translation\Loader\PhpFileLoader;
use Symfony\Component\Translation\MessageSelector;
use Symfony\Component\Translation\Translator;

// Use the ridiculously long Symfony namespaces


require __DIR__ . '/../vendor/autoload.php';

session_start();

$debug = false;
$app = new App([
    'settings' => [
        'debug' => $debug,
        'determineRouteBeforeAppMiddleware' => true,
        'displayErrorDetails' => $debug
    ],
]);

$container = $app->getContainer();

$capsule = new Manager;
$capsule->addConnection(require('config.php'));
$capsule->setAsGlobal();
$capsule->bootEloquent();

$container['db'] = function ($container) use ($capsule) {
    return $capsule;
};

// First param is the "default language" to use.
$language = Utils::getCurrentLanguage();
$translator = new Translator($language, new MessageSelector());
$translator->setFallbackLocales(['en']);
$translator->addLoader('php', new PhpFileLoader());
$translator->addResource('php', __DIR__ . '/../resources/lang/cn.php', 'cn'); // Norwegian
$translator->addResource('php', __DIR__ . '/../resources/lang/en.php', 'en'); // English

$container['view'] = function ($container) use ($translator) {
    $view = new Twig(__DIR__ . '/../resources/views', [
        'cache' => false,
    ]);

    $view->addExtension(new Slim\Views\TwigExtension(
            $container['router'],
            rtrim(str_ireplace('index.php', '', $container['request']->getUri()->getBasePath()), '/')
        )
    );
    $view->addExtension(new TranslationExtension($translator));
    return $view;
};

require __DIR__ . '/../routes/web.php';