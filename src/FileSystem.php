<?php

namespace FsApi;

/**
 * FileSystem класс для работы с файловой системой.
 * Нужен для выноса проверок над файлом и инициализацией папки.
 */
class FileSystem {
	private $initDir = "";

	/**
	 * fullName возвращает полный путь к файлу
	 * @param string $fileName
	 * @return string
	 */
	private function fullName($fileName) {
		$fullName = $this->initDir.'/'.$fileName;
		if (realpath($fullName)) {
			$fullName = realpath($fullName);
		}
		return $fullName;
	}

	/**
	 * @param string initDir директория, к которой нужен доступ
	 */
	public function __construct($initDir) {
		$initDir = rtrim($initDir, '/');
		$initDir = realpath($initDir);
		if (!file_exists($initDir)) {
			throw new \JsonApiMiddlewareNotFoundException('InitDir not found');
		}
		$this->initDir = $initDir;
	}

	public function getInitDir() {
		return $this->initDir;
	}

	/**
	 * ls возвращает список файлов
	 * @return array.<string>
	 */
	public function ls() {
		function fileRange($path) {
			if ($handle = opendir($path)) {
				while (false !== ($entry = readdir($handle))) {
					if ($entry != "." && $entry != "..") {
						yield $entry;
					}
				}
				closedir($handle);
			}
		}
		$list = [];
		foreach (fileRange($this->initDir) as $file) {
			$list []= $file;
		}
		return $list;
	}

	/**
	 * Возвращает файл с нужными методами
	 */
	public function file($fileName, $checkExistance = true) {
		$fullName = $this->fullName($fileName);
		if ($checkExistance && !file_exists($fullName)) {
			throw new \JsonApiMiddlewareNotFoundException('File not found');
		}
		// попытка прочесть файлы вне корневой папки (взлом)
		if (strpos($fullName, $this->initDir) !== 0) {
			throw new \Exception('Bad request'); // TODO: code 400
		}
		return new File($fullName);
	}
}

/**
 * Работа с файлом. Ожидает, что все проверки сделаны до его инициализации.
 */
class File {
	private $fileName = "";

	public function __construct($fileName) {
		$this->fileName = $fileName;
	}

	/**
	 * exists проверяет существование файла
	 * @return bool
	 */
	public function exists() {
		return file_exists($this->fileName);
	}

	/**
	 * get отдаёт содержимое файла
	 * @return string
	 */
	public function get() {
		return file_get_contents($this->fileName);
	}

	/**
	 * meta отдаёт метаданные файла
	 * @param array.<string> spec нужные метаданные. Если не указано, то вернёт все данные.
	 * @return array.<string, string>
	 */
	public function meta($spec = []) {
		$meta = stat($this->fileName);
		foreach ($meta as $key => $value) {
			if (is_numeric($key)) {
				unset($meta[$key]);
			} elseif (count($spec) > 0 && !in_array($key, $spec)) {
				unset($meta[$key]);
			}
		}
		return $meta;
	}

	/**
	 * put записывает в файл новые данные
	 * @param string $content
	 */
	public function put($content) {
		return file_put_contents($this->fileName, $content) !== false;
	}

	/**
	 * create создаёт файл
	 */
	public function create() {
		$this->put('');
	}

	/**
	 * delete удаляет файл
	 */
	public function delete() {
		unlink($this->fileName);
	}
}