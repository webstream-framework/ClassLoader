<?php

namespace WebStream\ClassLoader;

use RecursiveIteratorIterator;
use WebStream\DI\Injector;
use WebStream\IO\File;
use WebStream\IO\FileInputStream;

/**
 * クラスローダ
 * @author Ryuichi TANAKA.
 * @since 2013/09/02
 * @version 0.7
 */
class ClassLoader
{
    use Injector;

    /**
     * @var Psr\Log\LoggerInterface
     */
    private $logger;

    /**
     * @var string アプリケーションルートパス
     */
    private string $applicationRoot;

    /**
     * constructor
     * @param string アプリケーションルートパス
     */
    public function __construct(string $applicationRoot)
    {
        $this->logger = new class () { public function __call($name, $args) {} };
        $this->applicationRoot = $applicationRoot;
    }

    /**
     * クラスをロードする
     * @param mixed クラスまたはクラスリスト
     * @return array<string> ロード済みクラスリスト
     */
    public function load($target): array
    {
        return is_array($target) ? $this->loadClassList($target) : $this->loadClass($target);
    }

    /**
     * ファイルをインポートする
     * @param string ファイルパス
     * @param callable|null $filter フィルタリング無名関数 trueを返すとインポート
     * @return bool インポート結果
     * @throws \WebStream\Exception\Extend\IOException
     */
    public function import($filepath, callable $filter = null): bool
    {
        $file = new File($this->applicationRoot . "/" . $filepath);
        if ($file->isFile()) {
            if ($file->getFileExtension() === 'php') {
                if ($filter === null || (is_callable($filter) && $filter($file->getFilePath()) === true)) {
                    include_once $file->getFilePath();
                    $this->logger->debug($file->getAbsoluteFilePath() . " import success.");
                }
            }

            return true;
        }

        return false;
    }

    /**
     * 指定ディレクトリのファイルをインポートする
     * @param string ディレクトリパス
     * @param callable|null $filter フィルタリング無名関数 trueを返すとインポート
     * @return bool インポート結果
     * @throws \WebStream\Exception\Extend\IOException
     */
    public function importAll($dirPath, callable $filter = null): bool
    {
        $dir = new File($this->applicationRoot . "/" . $dirPath);
        $isSuccess = true;
        if ($dir->isDirectory()) {
            $iterator = $this->getFileSearchIterator($dir->getAbsoluteFilePath());
            foreach ($iterator as $filepath => $fileObject) {
                if (preg_match("/(?:\/\.|\/\.\.|\.DS_Store)$/", $filepath)) {
                    continue;
                }
                $file = new File($filepath);
                if ($file->isFile()) {
                    if ($file->getFileExtension() === 'php') {
                        if ($filter === null || (is_callable($filter) && $filter($file->getFilePath()) === true)) {
                            include_once $file->getFilePath();
                            $this->logger->debug($file->getAbsoluteFilePath() . " import success.");
                        }
                    }
                } else {
                    $this->logger->warn($filepath . " import failure.");
                    $isSuccess = false;
                }
            }
        }

        return $isSuccess;
    }

    /**
     * 名前空間リストを返却する
     * @param string ファイル名
     * @return array<string> 名前空間リスト
     * @throws \WebStream\Exception\Extend\IOException
     * @throws \WebStream\Exception\Extend\InvalidArgumentException
     */
    public function getNamespaces($fileName): array
    {
        $dir = new File($this->applicationRoot);
        $namespaces = [];
        if ($dir->isDirectory()) {
            $iterator = $this->getFileSearchIterator($dir->getAbsoluteFilePath());
            foreach ($iterator as $filepath => $fileObject) {
                if (preg_match("/(?:\/\.|\/\.\.|\.DS_Store)$/", $filepath)) {
                    continue;
                }
                $file = new File($filepath);
                if ($file->isFile() && $file->getFileName() === $fileName) {
                    $fis = new FileInputStream($file);
                    while (($line = $fis->readLine()) !== null) {
                        if (preg_match("/^namespace\s(.*);$/", $line, $matches)) {
                            $namespaces[] = $matches[1];
                        }
                    }
                    $fis->close();
                }
            }
        }

        return $namespaces;
    }

    /**
     * ロード可能なクラスを返却する
     * @param string クラス名(フルパス指定の場合はクラスパス)
     * @return array<string> ロード可能クラス
     * @throws \WebStream\Exception\Extend\IOException
     */
    private function loadClass(string $className): array
    {
        $rootDir = $this->applicationRoot;
        $logger = $this->logger;

        // 名前空間セパレータをパスセパレータに置換
        if (DIRECTORY_SEPARATOR === '/') {
            $className = str_replace("\\", DIRECTORY_SEPARATOR, $className);
        }

        $search = function ($dirPath, $searchFilePath) use ($logger) {
            $includeList = [];
            $dir = new File($dirPath);
            if (!$dir->isDirectory()) {
                $logger->error("Invalid search directory path: " . $dir->getFilePath());
                return $includeList;
            }
            $iterator = $this->getFileSearchIterator($dir->getAbsoluteFilePath());
            foreach ($iterator as $filepath => $fileObject) {
                if (!$fileObject->isFile()) {
                    continue;
                }
                if (strpos($filepath, $searchFilePath) !== false) {
                    $file = new File($filepath);
                    $absoluteFilePath = $file->getAbsoluteFilePath();
                    include_once $absoluteFilePath;
                    $includeList[] = $absoluteFilePath;
                    $logger->debug($absoluteFilePath . " load success. (search from " . $dir->getAbsoluteFilePath() . ")");
                }
            }

            return $includeList;
        };

        return $search("${rootDir}", DIRECTORY_SEPARATOR . "${className}.php");
    }

    /**
     * ロード可能なクラスを複数返却する
     * @param array クラス名
     * @return array<string> ロード済みクラスリスト
     * @throws \WebStream\Exception\Extend\IOException
     */
    private function loadClassList(array $classList): array
    {
        $includedList = [];
        foreach ($classList as $className) {
            $result = $this->loadClass($className);
            if (is_array($result)) {
                $includedList = array_merge($includedList, $result);
            }
        }

        return $includedList;
    }

    /**
     * ファイル検索イテレータを返却する
     * @param string ディレクトリパス
     * @return RecursiveIteratorIterator イテレータ
     */
    private function getFileSearchIterator(string $path): \RecursiveIteratorIterator
    {
        $iterator = [];
        $file = new File($path);
        if ($file->isDirectory()) {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($path),
                \RecursiveIteratorIterator::LEAVES_ONLY,
                \RecursiveIteratorIterator::CATCH_GET_CHILD // for Permission deny
            );
        }
        return $iterator;
    }
}
