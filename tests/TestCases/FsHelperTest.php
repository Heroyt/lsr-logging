<?php

namespace TestCases;

use Lsr\Logging\Exceptions\DirectoryCreationException;
use Lsr\Logging\FsHelper;
use PHPUnit\Framework\TestCase;

class FsHelperTest extends TestCase
{

	public static function joinPathProvider() : \Generator {
		yield [[], false, ''];
		yield [[], true, '/'];
		yield [['dir1'], false, 'dir1'];
		yield [['dir1', ' '], false, 'dir1'];
		yield [['dir1'], true, '/dir1'];
		yield [['dir1', '', 'dir2'], true, '/dir1/dir2'];
		yield [['dir1', '..'], false, ''];
		yield [['..', 'dir1'], false, '../dir1'];
		yield [['dir1', '..'], true, '/'];
		yield [['dir1', 'dir2', '..', 'dir3'], true, '/dir1/dir3'];
	}

	public static function extractPathProvider() : \Generator {
		yield ['', []];
		yield ['/', []];
		yield ['dir', ['dir']];
		yield ['/dir', ['dir']];
		yield ['/dir/dir2', ['dir', 'dir2']];
		yield ['/dir//dir2', ['dir', 'dir2']];
	}

	public static function winPathProvider() : \Generator {
		yield ['/dir1/dir2', '/', false];
		yield ['/dir1/dir2', '\\', false];
		yield ['\\dir1\\dir2', '\\', false];
		yield ['C:\\dir1\\dir2', '\\', true];
	}

	public function tearDown() : void {
		parent::tearDown();

		if (file_exists(LOG_DIR.'dir1/dir2/dir3')) {
			rmdir(LOG_DIR.'dir1/dir2/dir3');
			rmdir(LOG_DIR.'dir1/dir2');
			rmdir(LOG_DIR.'dir1');
		}
	}

	/**
	 * @dataProvider joinPathProvider
	 * @param string[] $path
	 * @param bool     $absolute
	 * @param string   $expected
	 * @return void
	 */
	public function testJoinPath(array $path, bool $absolute, string $expected) : void {
		$helper = FsHelper::getInstance();

		$actual = $helper->joinPath($path, $absolute);
		self::assertEquals($expected, $actual);
	}

	/**
	 * @dataProvider winPathProvider
	 * @param string $path
	 * @param bool   $expected
	 * @return void
	 */
	public function testCheckWinPath(string $path, string $separator, bool $expected) : void {
		$helper = new FsHelper($separator);
		self::assertEquals($expected, $helper->checkWinPath($path));
	}

	/**
	 * @dataProvider extractPathProvider
	 * @param string $path
	 * @param array  $expected
	 * @return void
	 */
	public function testExtractPath(string $path, array $expected) : void {
		$helper = FsHelper::getInstance();

		$actual = $helper->extractPath($path);
		self::assertEquals($expected, $actual);
	}

	public function testGetInstance() : void {
		$instance1 = FsHelper::getInstance();
		$instance2 = FsHelper::getInstance();
		self::assertSame($instance1, $instance2);
	}

	public function testCreateDirRecursive() : void {
		$directory = LOG_DIR.'dir1/dir2/dir3';

		// Prepare test environment
		if (file_exists(LOG_DIR.'dir1/dir2/dir3')) {
			rmdir(LOG_DIR.'dir1/dir2/dir3');
			rmdir(LOG_DIR.'dir1/dir2');
			rmdir(LOG_DIR.'dir1');
		}

		self::assertDirectoryDoesNotExist($directory);

		$helper = FsHelper::getInstance();

		$helper->createDirRecursive($helper->extractPath($directory));
		self::assertDirectoryExists($directory);
	}

	public function testCreateDirRecursiveInvalid() : void {
		$directory = PROTECTED_DIR.'/dir1';

		self::assertDirectoryDoesNotExist($directory);

		$this->expectException(DirectoryCreationException::class);
		$helper = FsHelper::getInstance();
		$helper->createDirRecursive($helper->extractPath($directory));
	}
}
