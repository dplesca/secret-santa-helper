<?php
require __DIR__ . '/../vendor/autoload.php';
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

$klein = new \Klein\Klein();

$klein->respond(function ($request, $response, $service, $app) use ($klein) {
    // $app also can store lazy services, e.g. if you don't want to
    // instantiate a database connection on every response
    $app->register('twig', function() {
  		$loader = new Twig_Loader_Filesystem( __DIR__ .'/../templates/');
		$twig = new Twig_Environment($loader, array(
		    'cache' => false
		));
		return $twig;
    });

    $app->register('log', function() {
    	$log = new Logger('secret_santa');
		$log->pushHandler(new StreamHandler(__DIR__ .'/../logs/santa.log', Logger::DEBUG));
		return $log;
    });

    $app->register('mailer', function(){
    	// Create the Transport and a swiftmailer instance
		$transport = Swift_SmtpTransport::newInstance('ssl://smtp.gmail.com', 465)
		  ->setUsername('')
		  ->setPassword('');
		$mailer = Swift_Mailer::newInstance($transport);
		return $mailer;
    });

    $app->register('message', function(){
    	return Swift_Message::newInstance()
    	  ->setSubject('Esti secret Santa pentru...')
  		  ->setFrom(array('secret@santa.com' => 'Secret Santa'))
  		  ->setContentType('text/html');
    });
});

$klein->respond('GET', '/', function ($request, $response, $service, $app) {
    return $app->twig->render('index.twig', array());
});

$klein->respond('POST', '/upload', function ($request, $response, $service, $app) {
	$sent_emails = array();
	$file = fopen($_FILES['csv']['tmp_name'], 'r');
	while (($data = fgetcsv($file, 1000, ",")) !== FALSE) {
		$people[] = array('name' => $data[0], 'email' => $data[1]);
	}
	fclose($file);

	$people = Utils::assign_users($people);
	echo '<pre>';print_r($people);die();
	foreach ($people as $key => $person){

	}

});

$klein->dispatch();

