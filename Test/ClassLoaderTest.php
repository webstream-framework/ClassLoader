<?php
namespace WebStream\ClassLoader\Test;

require_once dirname(__FILE__) . '/../Modules/DI/Injector.php';
require_once dirname(__FILE__) . '/../Modules/IO/File.php';
require_once dirname(__FILE__) . '/../ClassLoader.php';
require_once dirname(__FILE__) . '/../Test/Providers/ClassLoaderProvider.php';

use WebStream\ClassLoader\ClassLoader;
use WebStream\ClassLoader\Test\Providers\ClassLoaderProvider;

/**
* ClassLoaderTest
* @author Ryuichi TANAKA.
* @since 2017/01/21
* @version 0.7
 */
class ClassLoaderTest extends \PHPUnit_Framework_TestCase
{
    use ClassLoaderProvider;

    /**
     * 正常系
     * @test
     * @dataProvider loadProvider
     */
    public function okLoadTest($rootDir, $className)
    {
        $classLoader = new ClassLoader($rootDir);
        $this->assertCount(1, $classLoader->load($className));
    }

    /**
     * 正常系
     * @test
     * @dataProvider loadSubDirProvider
     */
    public function okLoadSubDirTest($rootDir, $className, $subDirList)
    {
        $classLoader = new ClassLoader($rootDir);
        $this->assertCount(1, $classLoader->load($className, $subDirList));
    }

    /**
     * 異常系
     * @test
     * @dataProvider unLoadProvider
     */
    public function ngLoadTest($rootDir, $className)
    {
        $classLoader = new ClassLoader($rootDir);
        $this->assertCount(0, $classLoader->load($className));
    }
}
