<?php

namespace App\Controller;

use App\Dto\PaginationDto;
use App\Entity\User;
use App\Service\UserService;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class UserController extends AbstractController
{
    public function __construct(
        private readonly UserService $userService,
        private readonly EntityManagerInterface $entityManager,
        private readonly ValidatorInterface $validator
    ) {
    }

    #[Route('/api/users/{id}/loans', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    #[OA\Get(
        path: '/api/users/{id}/loans',
        summary: 'Historia wypożyczeń użytkownika',
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'User ID',
                schema: new OA\Schema(type: 'integer'),
                example: 1
            ),
            new OA\Parameter(
                name: 'page',
                in: 'query',
                description: 'Page number',
                schema: new OA\Schema(type: 'integer', default: 1),
                example: 1
            ),
            new OA\Parameter(
                name: 'limit',
                in: 'query',
                description: 'Items per page',
                schema: new OA\Schema(type: 'integer', default: 10),
                example: 10
            )
        ],
        tags: ['Users'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Returns user loans history with pagination',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'loans',
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'id', type: 'integer'),
                                    new OA\Property(property: 'bookId', type: 'integer'),
                                    new OA\Property(property: 'userId', type: 'integer'),
                                    new OA\Property(property: 'loanDate', type: 'string', format: 'date-time'),
                                    new OA\Property(property: 'status', type: 'string', enum: ['lent', 'returned', 'overdue', 'lost']),
                                    new OA\Property(property: 'returnedAt', type: 'string', format: 'date-time', nullable: true)
                                ]
                            )
                        ),
                        new OA\Property(
                            property: 'pagination',
                            properties: [
                                new OA\Property(property: 'currentPage', type: 'integer'),
                                new OA\Property(property: 'totalPages', type: 'integer'),
                                new OA\Property(property: 'totalCount', type: 'integer'),
                                new OA\Property(property: 'limit', type: 'integer')
                            ]
                        )
                    ]
                )
            ),
            new OA\Response(response: 400, description: 'Validation errors - Invalid pagination parameters'),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 403, description: 'Access denied'),
            new OA\Response(response: 404, description: 'User not found')
        ],
        security: [['JWT' => []]],
    )]
    public function getUserLoans(int $id, Request $request): JsonResponse
    {
        $user = $this->entityManager->getRepository(User::class)->find($id);
        if (!$user) {
            return new JsonResponse(['error' => 'User not found'], 404);
        }

        /** @var User $currentUser */
        $currentUser = $this->getUser();
        if (!$this->isGranted('ROLE_LIBRARIAN')) {
            if ($currentUser->getId() !== $id) {
                return new JsonResponse(['error' => 'Access denied - You can only view your own loans'], 403);
            }
        }

        $page = $request->query->getInt('page', 1);
        $limit = $request->query->getInt('limit', 10);

        $paginationDto = new PaginationDto();
        $paginationDto->page = $page;
        $paginationDto->limit = $limit;

        $errors = $this->validator->validate($paginationDto);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getPropertyPath() . ': ' . $error->getMessage();
            }
            return new JsonResponse(['errors' => $errorMessages], 400);
        }

        $result = $this->userService->getUserLoansHistory($user, $paginationDto->page, $paginationDto->limit);

        return new JsonResponse([
            'loans' => array_map(function($loan) {
                return [
                    'id' => $loan->getId(),
                    'bookId' => $loan->getBook()->getId(),
                    'userId' => $loan->getUser()->getId(),
                    'loanDate' => $loan->getLoanDate()->format('c'),
                    'status' => $loan->getStatus()->value,
                    'returnedAt' => $loan->getReturnedAt()?->format('c')
                ];
            }, $result['loans']),
            'pagination' => $result['pagination']
        ]);
    }
}
