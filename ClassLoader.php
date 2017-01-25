<?php
namespace WebStream\ClassLoader;

use WebStream\DI\Injector;
use WebStream\IO\File;

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
    private $applicationRoot;

    /**
     * @var array<string> サブディレクトリパスリスト
     */
    private $subDirectoryPathList;

    /**
     * constructor
     * @param string アプリケーションルートパス
     * @param array<string> サブディレクトリパスリスト
     */
    public function __construct(string $applicationRoot, $subDirectoryPathList = [])
    {
        $this->logger = new class() { function __call($name, $args) {} };
        $this->applicationRoot = $applicationRoot;
        $this->subDirectoryPathList = $subDirectoryPathList;
    }

    /**
     * クラスをロードする
     * @param mixed クラス名
     * @return array<string> ロード済みクラスリスト
     */
    public function load(string $className): array
    {
        return is_array($className) ? $this->loadClassList($className) : $this->loadClass($className);
    }

    /**
     * ファイルをインポートする
     * @param string ファイルパス
     * @param callable フィルタリング無名関数 trueを返すとインポート
     * @return bool インポート結果
     */
    public function import($filepath, callable $filter = null): bool
    {
        $file = new File($this->applicationRoot . "/" . $filepath);
        if ($file->isFile()) {
            if ($file->getFileExtension() === 'php') {
                if ($filter === null || (is_callable($filter) && $filter($file->getFilePath()) === true)) {
                    include_once $file->getFilePath();
                    $this->logger->debug($file->getFilePath() . " import success.");
                }
            }

            return true;
        }

        return false;
    }

    /**
     * 指定ディレクトリのファイルをインポートする
     * @param string ディレクトリパス
     * @param callable フィルタリング無名関数 trueを返すとインポート
     * @return bool インポート結果
     */
    public function importAll($dirPath, callable $filter = null): bool
    {
        $dir = new File($this->applicationRoot . "/" . $dirPath);
        if ($dir->isDirectory()) {
            $iterator = $this->getFileSearchIterator($dir->getFilePath());
            $isSuccess = true;
            foreach ($iterator as $filepath => $fileObject) {
                if (preg_match("/(?:\/\.|\/\.\.|\.DS_Store)$/", $filepath)) {
                    continue;
                }
                $file = new File($filepath);
                if ($file->isFile()) {
                    if ($file->getFileExtension() === 'php') {
                        if ($filter === null || (is_callable($filter) && $filter($file->getFilePath()) === true)) {
                            include_once $file->getFilePath();
                            $this->logger->debug($file->getFilePath() . " import success.");
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
    * ロード可能なクラスを返却する
    * @param string クラス名(フルパス指定の場合はクラスパス)
    * @return array<string> ロード可能クラス
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
            $iterator = $this->getFileSearchIterator($dir->getFilePath());
            foreach ($iterator as $filepath => $fileObject) {
                if (!$fileObject->isFile()) {
                    continue;
                }
                if (strpos($filepath, $searchFilePath) !== false) {
                    $file = new File($filepath);
                    $absoluteFilePath = $file->getAbsoluteFilePath();
                    include_once $absoluteFilePath;
                    $includeList[] = $absoluteFilePath;
                    $logger->debug($absoluteFilePath . " load success. (search from " . $dir->getFilePath());
                }
            }

            return $includeList;
        };

        $includeList = $search("${rootDir}", DIRECTORY_SEPARATOR . "${className}.php");
        if (!empty($includeList)) {
            return $includeList;
        }

        foreach ($this->subDirectoryPathList as $searchPath) {
            if (preg_match("/(?:.*\/){0,}(.+)/", $className, $matches)) {
                $classNameWithoutNamespace = $matches[1];
                $includeList = $search("${rootDir}/${searchPath}", DIRECTORY_SEPARATOR . "${classNameWithoutNamespace}.php");
                if (!empty($includeList)) {
                    return $includeList;
                }
            }
        }

        return $includeList;
    }

    /**
     * ロード可能なクラスを複数返却する
     * @param array クラス名
     * @return array<string> ロード済みクラスリスト
     */
    private function loadClassList(array $classList): array
    {
        $includedlist = [];
        foreach ($classList as $className) {
            $result = $this->loadClass($className);
            if (is_array($result)) {
                $includedlist = array_merge($includedlist, $result);
            }
        }

        return $includedlist;
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
