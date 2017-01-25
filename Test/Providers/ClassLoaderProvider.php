<?php
namespace WebStream\ClassLoader\Test\Providers;

/**
 * ClassLoaderProvider
 * @author Ryuichi TANAKA.
 * @since 2017/01/22
 * @version 0.7
 */
trait ClassLoaderProvider
{
    public function loadProvider()
    {
        return [
            [dirname(__FILE__) . '/../Fixtures', 'Fixture1'],
            [dirname(__FILE__) . '/../Fixtures', 'Fixture2'],
            [dirname(__FILE__) . '/../', 'Fixtures\Sub\Fixture3']
        ];
    }

    public function loadSubDirProvider()
    {
        return [
            [dirname(__FILE__) . '/../Fixtures', 'Fixture3', ['Sub/']],
            [dirname(__FILE__) . '/../', 'Fixture3', ['Fixtures/Sub/']]
        ];
    }

    public function importProvider()
    {
        return [
            [dirname(__FILE__) . '/../Fixtures', 'ImportFixture1.php']
        ];
    }

    public function importAllProvider()
    {
        return [
            [dirname(__FILE__) . '/../Fixtures', 'ImportSub']
        ];
    }

    public function filteredImportProvider()
    {
        return [
            [dirname(__FILE__) . '/../Fixtures', 'ImportFixture1.php', dirname(__FILE__) . '/../Fixtures/ImportFixture1.php']
        ];
    }

    public function unLoadProvider()
    {
        return [
            [dirname(__FILE__) . '/../Dummy', 'Fixture1'],
            [dirname(__FILE__) . '/../Fixtures', 'Dummy']
        ];
    }
}
