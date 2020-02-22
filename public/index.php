<?php
require __DIR__ . '/../vendor/autoload.php';

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;

use \LINE\LINEBot;
use \LINE\LINEBot\HTTPClient\CurlHTTPClient;
use \LINE\LINEBot\MessageBuilder\MultiMessageBuilder;
use \LINE\LINEBot\MessageBuilder\TextMessageBuilder;
use \LINE\LINEBot\MessageBuilder\StickerMessageBuilder;
use \LINE\LINEBot\MessageBuilder\AudioMessageBuilder;
use \LINE\LINEBot\MessageBuilder\ImageMessageBuilder;
use \LINE\LINEBot\MessageBuilder\VideoMessageBuilder;
use \LINE\LINEBot\SignatureValidator as SignatureValidator;

$pass_signature = true;

// set LINE channel_access_token and channel_secret
$channel_access_token = "7q41nFkXI+YpZOEhIpyHOpEFpMsNTxLAQ/35aZPNC44TFqEaQuFGNej5CoqtRwP4+XAUEJhyCDbgdLaZQzVv6zC968HNarPG11WZJG3+1CJuYw/COkFMDU55f2uIw56qHufLM8+Vn3XDg81OUV/L+QdB04t89/1O/w1cDnyilFU=";
$channel_secret = "cedbf9efc5fcf0afd1c5aba1ddd9f40a";

// inisiasi objek bot
$httpClient = new CurlHTTPClient($channel_access_token);
$bot = new LINEBot($httpClient, ['channelSecret' => $channel_secret]);

$app = AppFactory::create();
$app->setBasePath("/public");

$app->get('/', function (Request $request, Response $response, $args) {
    $response->getBody()->write("Hello World!");
    return $response;
});

// buat route untuk webhook
$app->post('/webhook', function (Request $request, Response $response) use ($channel_secret, $bot, $httpClient, $pass_signature) {
    // get request body and line signature header
    $body = $request->getBody();
    $signature = $request->getHeaderLine('HTTP_X_LINE_SIGNATURE');

    // log body and signature
    file_put_contents('php://stderr', 'Body: ' . $body);

    if ($pass_signature === false) {
        // is LINE_SIGNATURE exists in request header?
        if (empty($signature)) {
            return $response->withStatus(400, 'Signature not set');
        }

        // is this request comes from LINE?
        if (!SignatureValidator::validateSignature($body, $channel_secret, $signature)) {
            return $response->withStatus(400, 'Invalid signature');
        }
    }

    $data = json_decode($body, true);
    if (is_array($data['events'])) {
        foreach ($data['events'] as $event) {
            if ($event['type'] == 'message') {
                if ($event['message']['type'] == 'text') {
                    // send same message as reply to user
                    $result = $bot->replyText($event['replyToken'], $event['message']['text']);

                    $bot->replyText($replyToken, 'ini pesan balasan');

                    $packageId = 1;
                    $stickerId = 3;
                    $stickerMessageBuilder = new StickerMessageBuilder($packageId, $stickerId);
                    $bot->replyMessage($replyToken, $stickerMessageBuilder);

                    // or we can use replyMessage() instead to send reply message
                    // $textMessageBuilder = new TextMessageBuilder($event['message']['text']);
                    // $result = $bot->replyMessage($event['replyToken'], $textMessageBuilder);


                    $response->getBody()->write($result->getJSONDecodedBody());
                    return $response
                        ->withHeader('Content-Type', 'application/json')
                        ->withStatus($result->getHTTPStatus());
                }
            }
        }
    }
});

$app->get('/pushmessage', function ($req, $response) use ($bot) {
    // send push message to user
    $userId = 'Ud87671ad3f635d81d1a142dc371d0d5c';
    $textMessageBuilder = new TextMessageBuilder('Halo, ini pesan push');
    $result = $bot->pushMessage($userId, $textMessageBuilder);

    $stickerMessageBuilder = new StickerMessageBuilder(1, 106);
    $bot->pushMessage($userId, $stickerMessageBuilder);

    $response->getBody()->write((string) $result->getJSONDecodedBody());
    return $response
        ->withHeader('Content-Type', 'application/json')
        ->withStatus($result->getHTTPStatus());
});

$app->get('/multicast', function($req, $response) use ($bot)
{
    // list of users
    $userList = [
        'Ud87671ad3f635d81d1a142dc371d0d5c',
        'U1afb282d2e367cc2396fbf1d8716a5c7',
        'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx',
        'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx',
        'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx'];
 
    // send multicast message to user
    $textMessageBuilder = new TextMessageBuilder('Halo, ini pesan multicast');
    $result = $bot->multicast($userList, $textMessageBuilder);
 
 
    $response->getBody()->write((string) $result->getJSONDecodedBody());
    return $response
        ->withHeader('Content-Type', 'application/json')
        ->withStatus($result->getHTTPStatus());
});

$app->get('/profile/{userId}', function($req, $response) use ($bot)
{
    // get user profile
    $route  = $req->getAttribute('route');
    $userId = $route->getArgument('userId');
    $result = $bot->getProfile($userId);
             
    $response->getBody()->write((string) $result->getJSONDecodedBody());
    return $response
        ->withHeader('Content-Type', 'application/json')
        ->withStatus($result->getHTTPStatus());
});

$app->run();
