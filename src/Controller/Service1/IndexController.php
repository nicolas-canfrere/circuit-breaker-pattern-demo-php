<?php

namespace App\Controller\Service1;

use Ackintosh\Ganesha\Exception\RejectedException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class IndexController extends AbstractController
{
    public function __construct(
        #[Autowire('@circuit_breaker.http_client')]
        private readonly HttpClientInterface $httpClient
    ) {
    }

    #[Route('/service1/index', name: 'app1_index', methods: ['GET'])]
    public function index(): Response
    {
        try {
            $response = $this->httpClient->request('GET', 'http://service2-nginx/service2/index');
            $result = $response->toArray();

            return $this->json(['message' => $result['message']], Response::HTTP_OK);
        } catch (RejectedException $exception) {
            return $this->json(['message' => 'fallback response', 'error' => $exception->getMessage()], Response::HTTP_OK);
        } catch (\Exception $exception) {

            return $this->json(['message' => $exception->getMessage(), 'exc' => get_class($exception)], 500);
        }
    }
}
