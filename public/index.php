<?php
require '../vendor/autoload.php';
require '../includes/config.php';

$app = new \Slim\Slim(array(
    'log.writer' => new \Slim\Extras\Log\DateTimeFileWriter(array(
        'path' => '../logs',
        'name_format' => 'Y-m-d',
        'message_format' => '%label% - %date% - %message%'
    ))
));

$app->get('/quote', function () use ($app) {
    $symbol = $app->request()->get('symbol');
    $quote = PHPMaster\StockService::GetQuote($symbol);
    var_dump($quote);
});

$app->get('/callback', function () use ($app, $config) {
    $log = $app->getLog();
    $sender = $app->request()->get('From');
    $message = $app->request()->get('Body');
    $log->info(sprintf('Message received from %s: %s', $sender, $message));

    $pattern = '/^([A-Z]*) ([A-Z]*)/';
    preg_match($pattern, $message, $matches);
    $command = $matches[1];
    $argument = $matches[2];

    switch ($command) {
        case 'PRICE':
            $log->info(sprintf('Executing command: %s', $command));
            $log->info(sprintf('Requesting price for: %s', $argument));

            $quote = PHPMaster\StockService::GetQuote($argument);

            $response = sprintf('%s: %s (%s) [Hi: %s Lo: %s]',
                $quote->Name,
                $quote->Last,
                $quote->Change,
                $quote->High,
                $quote->Low
            );

            $twilio_client = new Services_Twilio(               
                $config['twilio']['account_sid'],
                $config['twilio']['auth_token']
            );
             
            $sms = $twilio_client->account->sms_messages->create(
                $config['twilio']['phone_number'],  // the number to send from
                $sender,
                $response
            );
            break;

        default:
            $log->info(sprintf('Command: %s not found!', $command));
    }
});

$app->get('/', function () use ($app) {
    $message = 'PRICE AAPL';
    $pattern = '/^([A-Z]*) ([A-Z]*)/';
    preg_match($pattern, $message, $matches);
    $command = $matches[1];
    $argument = $matches[2];

    $quote = PHPMaster\StockService::GetQuote($argument);
    print $quote->Name;
    exit();
});

$app->run();