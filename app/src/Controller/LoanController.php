<?php

namespace App\Controller;

use App\Dto\CreateLoanDto;
use App\Entity\Book;
use App\Entity\Loan;
use App\Entity\User;
use App\Enum\LoanStatus;
use App\Enum\UserType;
use App\Service\LoanService;
use Doctrine\ORM\EntityManagerInterface;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class LoanController extends AbstractController
{
    public function __construct(
        private readonly LoanService $loanService,
        private readonly EntityManagerInterface $entityManager,
        private readonly SerializerInterface $serializer,
        private readonly ValidatorInterface $validator
    ) {
    }

    #[Route('/api/loans', methods: ['POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    #[OA\Post(
        path: '/api/loans',
        summary: 'Wypożyczenie książki',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: new Model(type: CreateLoanDto::class))
        ),
        tags: ['Loans'],
        responses: [
            new OA\Response(
                response: 201,
                description: 'Loan created successfully',
                content: new OA\JsonContent(ref: new Model(type: Loan::class))
            ),
            new OA\Response(response: 400, description: 'Validation error'),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 403, description: 'Access denied - Members can only create loans for themselves, Librarians cannot create loans for other librarians'),
            new OA\Response(response: 404, description: 'Book or User not found')
        ],
        security: [['JWT' => []]],
    )]
    public function createLoan(Request $request): JsonResponse
    {
        $createLoanDto = $this->serializer->deserialize(
            $request->getContent(),
            CreateLoanDto::class,
            'json'
        );

        $errors = $this->validator->validate($createLoanDto);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            return new JsonResponse(['errors' => $errorMessages], 400);
        }

        $book = $this->entityManager->getRepository(Book::class)->find($createLoanDto->bookId);
        if (!$book) {
            return new JsonResponse(['error' => 'Book not found'], 404);
        }

        $user = $this->entityManager->getRepository(User::class)->find($createLoanDto->userId);
        if (!$user) {
            return new JsonResponse(['error' => 'User not found'], 404);
        }

        /** @var User $currentUser */
        $currentUser = $this->getUser();
        
        if (!$this->isGranted('ROLE_LIBRARIAN')) {
            if ($currentUser->getId() !== $createLoanDto->userId) {
                return new JsonResponse(['error' => 'Access denied - You can only create loans for yourself'], 403);
            }
        } else {
            if ($currentUser->getId() !== $createLoanDto->userId) {
                if ($user->getType() === UserType::LIBRARIAN) {
                    return new JsonResponse(['error' => 'Access denied - Cannot create loans for other librarians'], 403);
                }
            }
        }

        $loan = $this->loanService->createLoan($book, $user);

        return $this->json($loan, 201, [], [
            'groups' => ['loan:read']
        ]);
    }

    #[Route('/api/loans/{id}/return', methods: ['PUT'])]
    #[IsGranted('ROLE_LIBRARIAN')]
    #[OA\Put(
        path: '/api/loans/{id}/return',
        summary: 'Zwrot książki (LIBRARIAN only)',
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'Loan ID',
                schema: new OA\Schema(type: 'integer'),
                example: 1
            )
        ],
        tags: ['Loans'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Book returned successfully',
                content: new OA\JsonContent(ref: new Model(type: Loan::class))
            ),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 403, description: 'Access denied - Only librarians can return books'),
            new OA\Response(response: 404, description: 'Loan not found'),
            new OA\Response(response: 400, description: 'Book already returned')
        ],
        security: [['JWT' => []]],
    )]
    public function returnBook(int $id): JsonResponse
    {
        $loan = $this->entityManager->getRepository(Loan::class)->find($id);
        if (!$loan) {
            return new JsonResponse(['error' => 'Loan not found'], 404);
        }

        if ($loan->getStatus() === LoanStatus::RETURNED) {
            return new JsonResponse(['error' => 'Book already returned'], 400);
        }

        $returnedLoan = $this->loanService->returnBook($loan);

        return $this->json($returnedLoan, 200, [], [
            'groups' => ['loan:read']
        ]);
    }
}
