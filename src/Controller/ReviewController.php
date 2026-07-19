<?php

namespace App\Controller;

use App\Entity\Review;
use App\Form\ReviewType;
use App\Repository\ReviewRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;

class ReviewController extends AbstractController
{
    #[Route('/', name: 'review_index', methods: ['GET'])]
    public function index(Request $request, ReviewRepository $reviewRepository): Response
    {
        $companyNameSearch = $request->query->getString('q') ?: null;

        return $this->render('review/index.html.twig', [
            'reviews' => $reviewRepository->findLatest($companyNameSearch),
            'companyNameSearch' => $companyNameSearch,
        ]);
    }

    #[Route('/reviews/new', name: 'review_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        RateLimiterFactory $reviewSubmissionLimiter,
    ): Response {
        $review = new Review();
        $form = $this->createForm(ReviewType::class, $review);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $limiter = $reviewSubmissionLimiter->create($request->getClientIp());

            if (!$limiter->consume(1)->isAccepted()) {
                $this->addFlash('danger', 'Túl sok véleményt küldtél be rövid idő alatt. Kérjük, próbáld újra néhány perc múlva.');
            } elseif ($form->isValid()) {
                $entityManager->persist($review);
                $entityManager->flush();

                $this->addFlash('success', 'Köszönjük a véleményed!');

                return $this->redirectToRoute('review_show', ['id' => $review->getId()]);
            }
        }

        return $this->render('review/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/reviews/{id}', name: 'review_show', methods: ['GET'])]
    public function show(Review $review): Response
    {
        return $this->render('review/show.html.twig', [
            'review' => $review,
        ]);
    }
}
