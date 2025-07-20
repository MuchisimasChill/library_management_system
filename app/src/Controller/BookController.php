<?php

namespace App\Controller;

use App\Dto\BookFilterDto;
use App\Dto\CreateBookDto;
use App\Entity\Book;
use App\Service\BookService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;
use OpenApi\Attributes as OA;
use Nelmio\ApiDocBundle\Attribute\Model;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class BookController extends AbstractController
{
    public function __construct(
        private readonly ValidatorInterface $validator,
        private readonly BookService $bookService,
        private readonly SerializerInterface $serializer,
    ) {}

    #[Route('/api/books', name: 'app_book', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    #[OA\Get(
        path: '/api/books',
        summary: 'Get paginated list of books with filtering',
        security: [['JWT' => []]],
        parameters: [
            new OA\Parameter(
                name: 'title',
                in: 'query',
                description: 'Filter by book title (partial match)',
                schema: new OA\Schema(type: 'string'),
                example: 'Harry Potter'
            ),
            new OA\Parameter(
                name: 'author',
                in: 'query',
                description: 'Filter by author name (partial match)',
                schema: new OA\Schema(type: 'string'),
                example: 'Rowling'
            ),
            new OA\Parameter(
                name: 'isbn',
                in: 'query',
                description: 'Filter by exact ISBN',
                schema: new OA\Schema(type: 'string'),
                example: '978-3-16-148410-0'
            ),
            new OA\Parameter(
                name: 'year',
                in: 'query',
                description: 'Filter by publication year',
                schema: new OA\Schema(type: 'integer'),
                example: 2001
            ),
            new OA\Parameter(
                name: 'pageNumber',
                in: 'query',
                schema: new OA\Schema(type: 'integer', default: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Returns paginated list of books with filtering',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(
                            property: 'books',
                            type: 'array',
                            items: new OA\Items(ref: new Model(type: Book::class))
                        ),
                        new OA\Property(property: 'totalCount', type: 'integer', description: 'Total number of books matching filters'),
                        new OA\Property(property: 'currentPage', type: 'integer', description: 'Current page number'),
                        new OA\Property(property: 'totalPages', type: 'integer', description: 'Total number of pages'),
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: 'Validation errors',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'errors', type: 'array', items: new OA\Items(type: 'string'))
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Authentication required - Missing or invalid JWT token',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'JWT Token not found')
                    ]
                )
            )
        ],
        tags: ['Books']
    )]
    public function listBooks(
        Request $request,
    ): JsonResponse {
        $filters = new BookFilterDto(
            pageNumber: $request->query->getInt('pageNumber', 1),
            title: $request->query->get('title'),
            year: $request->query->getInt('year') ?: null,
            author: $request->query->get('author'),
            isbn: $request->query->get('isbn'),
        );

        $errors = $this->validator->validate($filters);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getPropertyPath() . ': ' . $error->getMessage();
            }
            return $this->json(['errors' => $errorMessages], 400);
        }

        $books = $this->bookService->getBooks($filters);

        return $this->json($books, 200, [], [
            'groups' => ['book:list']
        ]);
    }

    #[Route('/api/books', name: 'app_book_create', methods: ['POST'])]
    #[IsGranted('ROLE_LIBRARIAN')]
    #[OA\Post(
        path: '/api/books',
        summary: 'Create a new book (LIBRARIAN only)',
        security: [['JWT' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: new Model(type: CreateBookDto::class))
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Book created successfully',
                content: new OA\JsonContent(ref: new Model(type: Book::class))
            ),
            new OA\Response(
                response: 400,
                description: 'Validation errors',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'errors', type: 'array', items: new OA\Items(type: 'string'))
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Authentication required',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'JWT Token not found')
                    ]
                )
            ),
            new OA\Response(
                response: 403,
                description: 'Access denied - Only librarians can create books',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Access Denied')
                    ]
                )
            )
        ],
        tags: ['Books']
    )]
    public function createBook(Request $request): JsonResponse
    {
        $createBookDto = $this->serializer->deserialize(
            $request->getContent(),
            CreateBookDto::class,
            'json'
        );

        $errors = $this->validator->validate($createBookDto);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            return new JsonResponse(['errors' => $errorMessages], 400);
        }

        try {
            $data = [
                'title' => $createBookDto->title,
                'author' => $createBookDto->author,
                'isbn' => $createBookDto->isbn,
                'year' => $createBookDto->year,
                'copies' => $createBookDto->copies
            ];
            
            $book = $this->bookService->createBook($data);
            return $this->json($book, 201, [], [
                'groups' => ['book:read']
            ]);
        } catch (\Exception $e) {
            return $this->json(['errors' => [$e->getMessage()]], 400);
        }
    }

    #[Route('/api/books/{id}', name: 'app_book_show', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    #[OA\Get(
        path: '/api/books/{id}',
        summary: 'Get book details by ID',
        security: [['JWT' => []]],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'Book ID',
                schema: new OA\Schema(type: 'integer'),
                example: 1
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Returns book details',
                content: new OA\JsonContent(ref: new Model(type: Book::class))
            ),
            new OA\Response(
                response: 404,
                description: 'Book not found',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'error', type: 'string', example: 'Book not found')
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Authentication required - Missing or invalid JWT token',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'JWT Token not found')
                    ]
                )
            )
        ],
        tags: ['Books']
    )]
    public function showBook(int $id): JsonResponse
    {
        $book = $this->bookService->getBookById($id);
        
        if (!$book) {
            return $this->json(['error' => 'Book not found'], 404);
        }

        return $this->json($book, 200, [], [
            'groups' => ['book:read']
        ]);
    }
}
