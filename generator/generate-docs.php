<?php

require __DIR__ . '/../vendor/autoload.php';

use Illuminate\Support\Arr;
use romanzipp\Twitch\Twitch;

$markdown = collect(class_uses(Twitch::class))
    ->map(function ($trait) {

        $title = str_replace('Trait', '', Arr::last(explode('\\', $trait)));

        $methods = [];

        $reflection = new ReflectionClass($trait);

        collect($reflection->getMethods())
            ->reject(function ($method) {
                return $method->isAbstract();
            })
            ->reject(function ($method) {
                return $method->isPrivate() || $method->isProtected();
            })
            ->reject(function ($method) {
                return $method->isConstructor();
            })
            ->each(function ($method) use (&$methods, $title, $trait) {

                $declaration = collect($method->getModifiers())->map(function (int $modifier) {
                    return $modifier == ReflectionMethod::IS_PUBLIC ? 'public ' : '';
                })->join(' ');

                $declaration .= 'function ';
                $declaration .= $method->getName();
                $declaration .= '(';

                $declaration .= collect($method->getParameters())->map(function (ReflectionParameter $parameter) {
                    return (Arr::last(explode('\\', $parameter->getType()->getName())) . ' ') . '$' . $parameter->getName();
                })->join(', ');

                $declaration .= ')';

                $methods[] = $declaration;
            });

        return [$title, $methods];
    })
    ->map(function ($args) {

        list($title, $methods) = $args;

        $markdown = '### ' . $title;
        $markdown .= PHP_EOL . PHP_EOL;
        $markdown .= '```php';
        $markdown .= PHP_EOL;

        $markdown .= collect($methods)->each(function ($method) {
            return $method;
        })->implode(PHP_EOL);

        $markdown .= PHP_EOL;
        $markdown .= '```';

        return $markdown;
    })->join(PHP_EOL . PHP_EOL);

$content = file_get_contents(__DIR__ . '/../README.stub');

$content = str_replace('<!-- GENERATED-DOCS -->', $markdown, $content);

file_put_contents(__DIR__ . '/../README.md', $content);