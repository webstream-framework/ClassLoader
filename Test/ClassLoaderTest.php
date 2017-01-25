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
     * loadが成功すること
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
     * サブディレクトリを指定してloadが成功すること
     * @test
     * @dataProvider loadSubDirProvider
     */
    public function okLoadSubDirTest($rootDir, $className, $subDirList)
    {
        $classLoader = new ClassLoader($rootDir, $subDirList);
        $this->assertCount(1, $classLoader->load($className));
    }

    /**
     * 正常系
     * importで指定ファイルをインポートできること
     * @test
     * @dataProvider importProvider
     */
    public function okImportTest($rootDir, $className)
    {
        $classLoader = new ClassLoader($rootDir);
        $this->assertTrue($classLoader->import($className));
        $this->assertTrue(class_exists(\WebStream\ClassLoader\Test\Fixtures\ImportFixture1::class));
    }

    /**
     * 正常系
     * importAllで指定ディレクトリ配下のファイルをすべてインポートできること
     * @test
     * @dataProvider importAllProvider
     */
    public function okImportAllTest($rootDir, $dirName)
    {
        $classLoader = new ClassLoader($rootDir);
        $this->assertTrue($classLoader->importAll($dirName));
        $this->assertTrue(class_exists(\WebStream\ClassLoader\Test\Fixtures\ImportFixture2::class));
        $this->assertTrue(class_exists(\WebStream\ClassLoader\Test\Fixtures\ImportFixture3::class));
    }

    /**
     * 正常系
     * importで指定ファイルをインポートできること
     * @test
     * @dataProvider filteredImportProvider
     */
    public function okFilteredImportTest($rootDir, $className, $ignoreClassName)
    {
        $classLoader = new ClassLoader($rootDir);
        $this->assertTrue($classLoader->import($className, function ($filepath) use ($ignoreClassName) {
            return $filepath === $ignoreClassName;
        }));
    }

    /**
     * 異常系
     * loadに失敗した場合、結果が0件になること
     * @test
     * @dataProvider unLoadProvider
     */
    public function ngLoadTest($rootDir, $className)
    {
        $classLoader = new ClassLoader($rootDir);
        $this->assertCount(0, $classLoader->load($className));
    }
}
