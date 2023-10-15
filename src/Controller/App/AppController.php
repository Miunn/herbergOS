<?php

namespace App\Controller\App;

use App\Entity\Containers;
use App\Entity\User;
use App\Services\AppService;
use App\Services\ContainerActivityService;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Finder\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use function Webmozart\Assert\Tests\StaticAnalysis\length;

#[Route('/app')]
class AppController extends AbstractController
{
    public function __construct(
        private readonly AppService $appService,
        private readonly ContainerActivityService $containerActivityService,
        private readonly TranslatorInterface $translator,
        private readonly EntityManagerInterface $entityManager
    ) {}

    #[Route('/', name: 'app_app_index')]
    public function index(): Response {
        $user = $this->getUser();

        if (!$user instanceof User) {
            throw new AccessDeniedException();
        }

        $userContainers = $this->appService->getUserContainers($user);
        return $this->render('app/index.twig', [
            'containers' => $userContainers,
        ]);
    }

    #[Route('/container/overview/{container}', name: 'app_container_overview')]
    public function container(Containers $container): Response {
        // Ensure user is associated with it
        $user = $this->getUser();
        if (!$user instanceof User || !$user->getContainers()->contains($container)) {
            throw new AccessDeniedException();
        }

        $containerApi = $this->appService->getContainer($container->getId());
        return $this->render('app/view/container-overview.twig', [
            'container' => $container,
            'containerApi' => $containerApi,
            'overview' => true
        ]);
    }

    #[Route('/container/stats/{container}', name: 'app_container_stats')]
    public function containerStats(Containers $container): Response {
        // Ensure user is associated with it
        $user = $this->getUser();
        if (!$user instanceof User || !$user->getContainers()->contains($container)) {
            throw new AccessDeniedException();
        }

        $containerApi = $this->appService->getContainer($container->getId());
        $stats = $this->appService->getStats($container, 'instant');
        return $this->render('app/view/container-stats.twig', [
            'container' => $container,
            'containerApi' => $containerApi,
            'data' => $stats,
            'stats' => true
        ]);
    }

    #[Route('/container/shell/{container}', name: 'app_container_shell')]
    public function containerShell(Containers $container): Response {
        $this->denyAccessUnlessGranted('ROLE_INFO');
        // Ensure user is associated with it
        $user = $this->getUser();
        if (!$user instanceof User || !$user->getContainers()->contains($container)) {
            throw new AccessDeniedException();
        }

        $containerApi = $this->appService->getContainer($container->getId());
        return $this->render('app/view/container-shell.twig', [
            'container' => $container,
            'containerApi' => $containerApi,
            'shell' => "1"
        ]);
    }

    #[Route('/container/actions/{container}', name: 'app_container_actions')]
    public function containerActions(Containers $container): Response {
        // Ensure user is associated with it
        $user = $this->getUser();
        if (!$user instanceof User || !$user->getContainers()->contains($container)) {
            throw new AccessDeniedException();
        }

        $containerApi = $this->appService->getContainer($container->getId());
        return $this->render('app/view/container-actions.twig', [
            'container' => $container,
            'containerApi' => $containerApi,
            'actions' => "1"
        ]);
    }

    /** AJAX Routes */

    #[Route('/container/stats-json/{container}', name: 'app_request_stats')]
    public function requestStats(Request $request, Containers $container): JsonResponse
    {
        // Ensure user is associated with it
        $user = $this->getUser();
        if (!$user instanceof User || !$user->getContainers()->contains($container)) {
            throw new AccessDeniedException();
        }

        $scale = $request->query->get('scale');

        if ($scale != 'instant' && $scale != 'day' && $scale != 'week') {
            return new JsonResponse(['error' => 'Bad scale'], 400);
        }

        return new JsonResponse($this->appService->getStats($container, $scale));
    }

    #[Route('/container/start/{container}', name: 'app_container_start')]
    public function startContainer(Containers $container): Response {
        $this->denyAccessUnlessGranted('ROLE_INFO');
        // Ensure user is associated with it
        $user = $this->getUser();
        if (!$user instanceof User || !$user->getContainers()->contains($container)) {
            throw new AccessDeniedException();
        }

        $response = $this->appService->startContainer($container);

        // Record in activities
        if ($response['success']) {
            $this->containerActivityService->recordActivity($container, $this->translator->trans('container.records.started'));
        }

        return new JsonResponse($response);
    }

    #[Route('/container/stop/{container}', name: 'app_container_stop')]
    public function stopContainer(Containers $container): Response {
        $this->denyAccessUnlessGranted('ROLE_INFO');
        // Ensure user is associated with it
        $user = $this->getUser();
        if (!$user instanceof User || !$user->getContainers()->contains($container)) {
            throw new AccessDeniedException();
        }

        $response = $this->appService->stopContainer($container);

        if ($response['success']) {
            $this->containerActivityService->recordActivity($container, $this->translator->trans('container.records.stopped'));
        }

        return new JsonResponse($response);
    }

    #[Route('/container/restart/{container}', name: 'app_container_restart')]
    public function restartContainer(Containers $container): Response {
        $this->denyAccessUnlessGranted('ROLE_INFO');
        // Ensure user is associated with it
        $user = $this->getUser();
        if (!$user instanceof User || !$user->getContainers()->contains($container)) {
            throw new AccessDeniedException();
        }

        $response = $this->appService->restartContainer($container);

        if ($response['success']) {
            $this->containerActivityService->recordActivity($container, $this->translator->trans('container.records.restarted'));
        }

        return new JsonResponse($response);
    }

    #[Route('/container/ask-delete/{container}', name: 'app_container_ask_delete')]
    public function askDeleteContainer(Containers $container): Response {
        return new Response("OK", 200);
    }

    #[Route('/container/ask-config/{container}', name: 'app_container_ask_config')]
    public function askConfigContainer(Containers $container): Response {
        return new Response("OK", 200);
    }
}