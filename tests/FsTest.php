<?php

class FsTest extends PHPUnit_Framework_TestCase
{
	private $fs = null;
	const INIT_DIR = '/tmp/FileSystemApiTestDir';

	public function __construct() {
		if (!file_exists(self::INIT_DIR)) {
			mkdir(self::INIT_DIR);
		}
		$this->fs = new \FsApi\FileSystem(self::INIT_DIR);
	}

	public function __destruct() {
		system('rm -rf '.self::INIT_DIR);
	}

	public function testConstruct() {
		$fs = new \FsApi\FileSystem(self::INIT_DIR.'//////');
		$this->assertEquals(self::INIT_DIR, $fs->getInitDir());
	}

	public function testExceptionInitDirMissing() {
		$this->setExpectedException('JsonApiMiddlewareNotFoundException');
		$fs = new \FsApi\FileSystem('/hope/there/is/no/such/directory/in/the/system');
	}

	public function testClass() {
		$this->assertClassHasAttribute('initDir', '\FsApi\FileSystem');
	}

	public function testCanCreateFile() {
		$fileName = 'create';
		$this->fs->file($fileName, false)->create();
		$this->assertFileExists(self::INIT_DIR.'/'.$fileName);
	}

	public function testCanWriteAndGet() {
		$fs = $this->fs;
		$str = 'Put the cookie down!';
		$fileName = 'writeAndGet';
		$fs->file($fileName, false)->put($str);
		$strFromFile = $fs->file($fileName)->get();
		$this->assertEquals($str, $strFromFile);
	}

	public function testCanGetMeta() {
		$fileName = 'meta';
		$fs = $this->fs;
		$fs->file($fileName, false)->create();
		$meta = $fs->file($fileName)->meta();
		$this->assertArrayHasKey('atime', $meta);

		$meta = $fs->file($fileName)->meta(['mtime']);
		$this->assertArrayHasKey('mtime', $meta);
		$this->assertFalse(isset($meta['atime']));
	}

	public function testExists() {
		$fs = $this->fs;
		$fileName = 'exists';
		$this->assertFalse($fs->file($fileName, false)->exists());
		$fs->file($fileName, false)->create();
		$this->assertTrue($fs->file($fileName, false)->exists());
	}

	public function testExceptionFileNotFound()
	{
		$this->setExpectedException('JsonApiMiddlewareNotFoundException');
		$this->fs->file('notfound')->get();
	}

	public function testExceptionSecurty() {
		$this->setExpectedException('Exception', 'Bad request');
		$this->fs->file('../../etc/passwd')->get();
	}

	public function testList() {
		$fs = $this->fs;
		$fileName = 'list';
		$fs->file($fileName, false)->create();
		$list = $fs->ls();
		$this->assertContains($fileName, $list);
	}

	public function testDelete() {
		$fs = $this->fs;
		$fileName = 'delete';
		$fs->file($fileName, false)->create();
		$fs->file($fileName)->delete();
		$this->assertFalse(file_exists(self::INIT_DIR.'/'.$fileName));
	}
}