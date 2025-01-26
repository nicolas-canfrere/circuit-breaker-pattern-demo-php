<?php

namespace App\Factory;

use Ackintosh\Ganesha;
use Ackintosh\Ganesha\Builder;
use Ackintosh\Ganesha\GaneshaHttpClient;
use Ackintosh\Ganesha\HttpClient\RestFailureDetector;
use Ackintosh\Ganesha\Storage\Adapter\Redis;
use Predis\Client;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class CircuitBreakerHttpClientFactory
{
    public static function create(
        Client $redis,
        LoggerInterface $logger,
        $timeWindow = 15, // sur les 15 dernières secondes
        $failureRateThreshold = 50, // si 50% des requêtes ont échoué
        $minimumRequests = 3, // et qu'il y a eu au moins 3 requêtes => OPEN
        $intervalToHalfOpen = 5 // alors on attend 5 secondes avant de réessayer => HALF-OPEN
    ): HttpClientInterface {
        $adapter = new Redis($redis);
        $ganesha = Builder::withRateStrategy()
            ->timeWindow($timeWindow)
            ->failureRateThreshold($failureRateThreshold)
            ->minimumRequests($minimumRequests)
            ->intervalToHalfOpen($intervalToHalfOpen)
            ->adapter($adapter)
            ->build();

        $ganesha->subscribe(function ($event, $service, $message) use ($logger) {
            switch ($event) {
                case Ganesha::EVENT_TRIPPED:
                    $logger->error(
                        "Ganesha has tripped! It seems that a failure has occurred in {$service}. {$message}."
                    );
                    break;
                case Ganesha::EVENT_CALMED_DOWN:
                    $logger->info(
                        "The failure in {$service} seems to have calmed down :). {$message}."
                    );
                    break;
                case Ganesha::EVENT_STORAGE_ERROR:
                    $logger->error($message);
                    break;
                default:
                    break;
            }
        });

        $client = HttpClient::create();

        return new GaneshaHttpClient(
            $client,
            $ganesha,
            null,
            new RestFailureDetector([500, 502, 503, 504, 505]) // on considère ces codes comme des erreurs
        );
    }
}
