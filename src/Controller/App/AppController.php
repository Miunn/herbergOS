<?php

namespace App\Controller\App;

use App\Entity\Containers;
use App\Entity\User;
use App\Services\AppService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Finder\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/app')]
class AppController extends AbstractController
{
    public function __construct(
        private readonly AppService $appService
    ) {}

    #[Route('/', name: 'app_app_index')]
    public function index(EntityManagerInterface $entityManager): Response {
        $user = $this->getUser();

        if (!$user instanceof User) {
            throw new AccessDeniedException();
        }

        $userContainers = $this->appService->getUserContainers($user, $entityManager);
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

        $overview = $this->appService->getContainer($container->getId());
        return $this->render('app/view/container-overview.twig', [
            'container' => $container,
            'overview' => $overview
        ]);
    }

    #[Route('/container/stats/{container}', name: 'app_container_stats')]
    public function containerStats(Containers $container): Response {
        // Ensure user is associated with it
        $user = $this->getUser();
        if (!$user instanceof User || !$user->getContainers()->contains($container)) {
            throw new AccessDeniedException();
        }

        $stats = $this->appService->getContainerStats($container->getId(), 0);
        return $this->render('app/view/container-stats.twig', [
            'container' => $container,
            'stats' => $stats
        ]);
    }

    #[Route('/container/shell/{container}', name: 'app_container_shell')]
    public function containerShell(Containers $container): Response {
        // Ensure user is associated with it
        $user = $this->getUser();
        if (!$user instanceof User || !$user->getContainers()->contains($container)) {
            throw new AccessDeniedException();
        }

        $stats = $this->appService->getContainerStats($container->getId(), 0);
        return $this->render('app/view/container-shell.twig', [
            'container' => $container,
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

        $stats = $this->appService->getContainerStats($container->getId(), 0);
        return $this->render('app/view/container-actions.twig', [
            'container' => $container,
            'actions' => "1"
        ]);
    }
}