<?php
require 'vendor/autoload.php';

$extraWriter = new \Slim\Extras\Log\DateTimeFileWriter(
								[
									'path'           => './log',
									'name_format'    => 'Y-m-d',
									'message_format' => '%label% - %date% - %message%'
								]
							);
$conf = ['log.writer' => $extraWriter, 'log.enabled' => true, 'log.level' => \Slim\Log::INFO];
$app = new \Slim\Slim($conf);

// инициализация работы с фс
$fs  = new \FsApi\FileSystem("./tmp");

// отдача в json и канонизированные ответы
$app->view(new \JsonApiView());
$app->add(new \JsonApiMiddleware());

// авторизация
$app->add(new \Slim\Extras\Middleware\HttpBasicAuth('xsolla', 'xsolla'));

// логи
$app->hook('slim.after', function () use ($app) {
	$request = $app->request;
	$response = $app->response;

	$str = $response->getStatus().' '.$app->request()->getIp().' '.$request->getPathInfo();
	$app->log->info($str);
});

$app->group('/api', function() use ($app, $fs) {
	$app->group('/v1', function() use ($app, $fs) {
		// пинг-понг проверка сервиса
		$app->get('/ping', function() use ($app) {
			$app->render(200);
		});

		// проверка существования файла или директории
		$app->map('/file/:fileName', function($fileName) use ($app, $fs) {
			$status = $fs->file($fileName, false)->exists() ? 200 : 404;
			$app->render($status);
		})->via('HEAD');

		// получение содержимого файла
		$app->get('/file/:fileName', function($fileName) use ($app, $fs) {
			$content = $fs->file($fileName)->get();
			$app->render(200, ['content' => $content]);
		});

		// получение метаданных
		$app->options('/file/:fileName', function($fileName) use ($app, $fs) {
			$spec = [];
			if ($app->request->get('spec')) {
				$spec = explode(',', $app->request->get('spec'));
			}
			$meta = $fs->file($fileName)->meta($spec);
			$app->render(200, ['meta' => $meta]);
		});

		// запись данных
		$app->put('/file/:fileName', function($fileName) use ($app, $fs) {
			$content = $app->request->getBody();
			$status = $fs->file($fileName, false)->put($content);
			if ($status) {
				$app->render(200, []);
			}
		});

		// создать файл
		$app->post('/file/:fileName', function($fileName) use ($app, $fs) {
			$fs->file($fileName, false)->create();
			$app->render(200);
		});

		// создать файл
		$app->delete('/file/:fileName', function($fileName) use ($app, $fs) {
			$fs->file($fileName)->delete();
			$app->render(200);
		});

		// список файлов
		$app->get('/file/', function() use ($app, $fs) {
			$list = $fs->ls();
			$app->render(200, ['list' => $list]);
		});
	});
});

$app->run();