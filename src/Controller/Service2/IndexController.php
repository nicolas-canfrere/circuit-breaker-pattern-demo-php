<?php

namespace App\Controller\Service2;

use Predis\Client;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class IndexController extends AbstractController
{
    public function __construct(
        #[Autowire('@snc_redis.default')]
        private readonly Client $redis
    ) {
    }

    #[Route('/service2/index', name: 'app2_index', methods: ['GET'])]
    public function index(): Response
    {
        $currentState = $this->redis->get('service2_state');
        if (null !== $currentState && ServiceStateEnum::ON_ERROR->value === $currentState) {
            return new JsonResponse(['message' => 'SERVICE2 ON ERROR!!'], 500);
        }

        return new JsonResponse(['message' => 'all is fine'], 200);
    }

    #[Route('/service2/change-state', name: 'app2_change_state', methods: ['POST'])]
    public function changeState(Request $request): Response
    {
        $data = \json_decode($request->getContent(), true);
        $state = $data['new_state'] ?? ServiceStateEnum::RUNNING->value;
        $serviceState = ServiceStateEnum::from($state);
        $this->redis->set('service2_state', $serviceState->value);

        return new JsonResponse(['message' => 'state changed', 'new_state' => $serviceState->value], 200);
    }
}
