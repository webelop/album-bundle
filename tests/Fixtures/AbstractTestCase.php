<?php


namespace Webelop\AlbumBundle\Tests\Fixtures;


use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Webelop\AlbumBundle\Tests\Fixtures\App\AppKernel;

/**
 * Class AbstractControllerTest
 */
abstract class AbstractTestCase extends WebTestCase
{
    protected static function getKernelClass()
    {
        return AppKernel::class;
    }

    protected function setUp(): void
    {
        $this->initDatabase();
    }

    /**
     * It ensures that the database contains the original fixtures of the
     * application. This way tests can modify its contents safely without
     * interfering with subsequent tests.
     *
     * Attributed to https://github.com/EasyCorp/EasyAdminBundle/
     */
    protected function initDatabase()
    {
        $buildDir = __DIR__ . '/../../build';
        $originalDbPath = $buildDir.'/original_test.db';
        $targetDbPath = $buildDir.'/test.db';

        if (!file_exists($originalDbPath)) {
            throw new \RuntimeException(sprintf("The fixtures file used for the tests (%s) doesn't exist. This means that the execution of the bootstrap.php script that generates that file failed. Open %s/bootstrap.php and replace `NullOutput as ConsoleOutput` by `ConsoleOutput` to see the actual errors in the console.", $originalDbPath, realpath(__DIR__ . '/tests')));
        }

        copy($originalDbPath, $targetDbPath);
    }


}

