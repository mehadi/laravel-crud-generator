<?php

// autoload_real.php @generated by Composer

class ComposerAutoloaderInit71c79c444392a6df2ec41fd983c50cda
{
    private static $loader;

    public static function loadClassLoader($class)
    {
        if ('Composer\Autoload\ClassLoader' === $class) {
            require __DIR__ . '/ClassLoader.php';
        }
    }

    /**
     * @return \Composer\Autoload\ClassLoader
     */
    public static function getLoader()
    {
        if (null !== self::$loader) {
            return self::$loader;
        }

        spl_autoload_register(array('ComposerAutoloaderInit71c79c444392a6df2ec41fd983c50cda', 'loadClassLoader'), true, true);
        self::$loader = $loader = new \Composer\Autoload\ClassLoader(\dirname(__DIR__));
        spl_autoload_unregister(array('ComposerAutoloaderInit71c79c444392a6df2ec41fd983c50cda', 'loadClassLoader'));

        require __DIR__ . '/autoload_static.php';
        call_user_func(\Composer\Autoload\ComposerStaticInit71c79c444392a6df2ec41fd983c50cda::getInitializer($loader));

        $loader->register(true);

        return $loader;
    }
}
