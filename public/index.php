<?php
require __DIR__ . '/../vendor/autoload.php';
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Symfony\Component\Yaml\Yaml;


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

    $app->texts = array(
		'Cadourile se aduc miercuri, 18 decembrie, cu eticheta cu numele persoanei ce trebuie sa il primeasca si se lasa in jurul bradului.',
		'Pentru cei care lucreaza atunci in tura de seara: hai sa incercam sa ne strangem pe la 12:30, ca sa si aduceti, dar sa si primiti si voi cadourile.'
	);

	$app->price = 'Secret Santa inseamna cadouri simbolice.. O suma orientativa ar fi undeva in jurul a 50 de lei/cadou.';

    $app->register('mailer', function(){
    	// Create the Transport and a swiftmailer instance
		$config = Yaml::parse(file_get_contents( __DIR__ . '/../config/config.yaml'));
		$transport = Swift_SmtpTransport::newInstance($config['host'], $config['port'])
		  ->setUsername($config['username'])
		  ->setPassword($config['password']);

		$mailer = Swift_Mailer::newInstance($transport);
		return $mailer;
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
	$app->log->addInfo(print_r($people, true));
	foreach ($people as $key => $person){
		$message = Swift_Message::newInstance()
		  ->setSubject('Esti secret Santa pentru...')
		  ->setFrom(array('secret@santa.com' => 'Secret Santa'))
		  ->setContentType('text/html')
		  ->setTo(array($person['email'] => $person['name']))
		  ->setBody($app->twig->render('mail.twig', array('texts' => $app->texts, 'person' => $person, 'price' => $app->price)));
		try{
			$result = $app->mailer->send($message);
		} catch(Exception $e){
			$app->log->addError($e->getMessage());
		}
	}
	echo '1';
});

$klein->dispatch();

