<?php

namespace WebStream\ClassLoader\Test;

require_once dirname(__FILE__) . '/../Modules/DI/Injector.php';
require_once dirname(__FILE__) . '/../Modules/IO/File.php';
require_once dirname(__FILE__) . '/../Modules/IO/InputStream.php';
require_once dirname(__FILE__) . '/../Modules/IO/FileInputStream.php';
require_once dirname(__FILE__) . '/../ClassLoader.php';
require_once dirname(__FILE__) . '/../Test/Providers/ClassLoaderProvider.php';
require_once dirname(__FILE__) . '/../Test/Fixtures/DummyLogger.php';

use PHPUnit\Framework\TestCase;
use WebStream\ClassLoader\ClassLoader;
use WebStream\ClassLoader\Test\Fixtures\DummyLogger;
use WebStream\ClassLoader\Test\Providers\ClassLoaderProvider;

/**
 * ClassLoaderTest
 * @author Ryuichi TANAKA.
 * @since 2017/01/21
 * @version 0.7
 */
class ClassLoaderTest extends TestCase
{
    use ClassLoaderProvider;

    /**
     * 正常系
     * loadが成功すること
     * @test
     * @dataProvider loadProvider
     * @param $rootDir
     * @param $className
     */
    public function okLoadTest($rootDir, $className)
    {
        $classLoader = new ClassLoader($rootDir);
        $this->assertCount(1, $classLoader->load($className));
    }

    /**
     * 正常系
     * loadが成功すること(リスト読み込み)
     * @dataProvider loadListProvider
     * @param $rootDir
     * @param $classList
     */
    public function okLoadListTest($rootDir, $classList)
    {
        $classLoader = new ClassLoader($rootDir);
        $this->assertCount(2, $classLoader->load($classList));
    }

    /**
     * 正常系
     * サブディレクトリを指定してloadが成功すること
     * @test
     * @dataProvider loadSubDirProvider
     * @param $rootDir
     * @param $className
     * @param $subDirList
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
     * @param $rootDir
     * @param $className
     * @throws \WebStream\Exception\Extend\IOException
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
     * @param $rootDir
     * @param $dirName
     * @throws \WebStream\Exception\Extend\IOException
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
     * フィルタ付きimportで指定ファイルをインポートできること
     * @test
     * @dataProvider filteredImportProvider
     * @param $rootDir
     * @param $className
     * @param $ignoreClassName
     * @throws \WebStream\Exception\Extend\IOException
     */
    public function okFilteredImportTest($rootDir, $className, $ignoreClassName)
    {
        $classLoader = new ClassLoader($rootDir);
        $this->assertTrue($classLoader->import($className, function ($filepath) use ($ignoreClassName) {
            return $filepath === $ignoreClassName;
        }));
        $this->assertTrue(class_exists(\WebStream\ClassLoader\Test\Fixtures\ImportFixture4::class));
    }

    /**
     * 正常系
     * フィルタ付きimportAllで指定ファイルをインポートできること
     * @test
     * @dataProvider filteredImportAllProvider
     * @param $rootDir
     * @param $dirName
     * @param $ignoreClassName
     * @throws \WebStream\Exception\Extend\IOException
     */
    public function okFilteredImportAllTest($rootDir, $dirName, $ignoreClassName)
    {
        $classLoader = new ClassLoader($rootDir);
        $this->assertTrue($classLoader->importAll($dirName, function ($filepath) use ($ignoreClassName) {
            return $filepath === $ignoreClassName;
        }));
        $this->assertTrue(class_exists(\WebStream\ClassLoader\Test\Fixtures\ImportFixture5::class));
    }

    /**
     * 正常系
     * 指定ファイルの名前空間が取得できること
     * @test
     * @dataProvider loadNamespaceProvider
     * @param $rootDir
     * @param $filePath
     * @param $list
     * @throws \WebStream\Exception\Extend\IOException
     * @throws \WebStream\Exception\Extend\InvalidArgumentException
     */
    public function okLoadNamespaceTest($rootDir, $filePath, $list)
    {
        $classLoader = new ClassLoader($rootDir);
        $namespaces = $classLoader->getNamespaces($filePath);
        foreach ($list as $namespace) {
            $this->assertTrue(in_array($namespace, $namespaces, true));
        }
    }

    /**
     * 異常系
     * loadに失敗した場合、結果が0件になること
     * @test
     * @dataProvider unLoadProvider
     * @param $rootDir
     * @param $className
     */
    public function ngLoadTest($rootDir, $className)
    {
        $classLoader = new ClassLoader($rootDir);
        $this->assertCount(0, $classLoader->load($className));
    }

    /**
     * 異常系
     * @test
     * 存在しないファイルはインポートできないこと
     * @dataProvider unImportProvider
     * @param $rootDir
     * @throws \WebStream\Exception\Extend\IOException
     */
    public function ngImportTest($rootDir)
    {
        $classLoader = new ClassLoader($rootDir);
        $this->assertFalse($classLoader->import("Dummy.php"));
    }

    /**
     * 異常系
     * フィルタにマッチしない場合、importAllで指定ファイルをインポートできないこと
     * @test
     * @dataProvider filteredImportAllProvider
     * @param $rootDir
     * @param $dirName
     * @param $ignoreClassName
     * @throws \WebStream\Exception\Extend\IOException
     */
    public function ngFilteredImportAllTest($rootDir, $dirName, $ignoreClassName)
    {
        $classLoader = new ClassLoader($rootDir);
        $classLoader->inject('logger', new DummyLogger());
        $classLoader->importAll($dirName, function ($filepath) use ($ignoreClassName) {
            return false;
        });
        $this->expectOutputString('');
    }

    /**
     * 異常系
     * 指定ファイルの名前空間が取得できないこと
     * @test
     * @dataProvider unLoadNamespaceProvider
     * @param $rootDir
     * @param $filePath
     * @param $result
     * @throws \WebStream\Exception\Extend\IOException
     * @throws \WebStream\Exception\Extend\InvalidArgumentException
     */
    public function ngLoadNamespaceTest($rootDir, $filePath, $result)
    {
        $classLoader = new ClassLoader($rootDir);
        $this->assertEquals($classLoader->getNamespaces($filePath), $result);
    }
}
