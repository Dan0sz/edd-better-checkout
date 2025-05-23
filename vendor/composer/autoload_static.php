<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit19d00e0c708fa7d852318e5e8e6c2e47
{
    public static $prefixLengthsPsr4 = array (
        'D' => 
        array (
            'Daan\\EDD\\BetterCheckout\\' => 24,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Daan\\EDD\\BetterCheckout\\' => 
        array (
            0 => __DIR__ . '/../..' . '/src',
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit19d00e0c708fa7d852318e5e8e6c2e47::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit19d00e0c708fa7d852318e5e8e6c2e47::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInit19d00e0c708fa7d852318e5e8e6c2e47::$classMap;

        }, null, ClassLoader::class);
    }
}
