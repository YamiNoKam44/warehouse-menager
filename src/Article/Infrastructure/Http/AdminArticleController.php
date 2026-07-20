<?php

declare(strict_types=1);

namespace App\Article\Infrastructure\Http;

use App\Article\Application\Dto\ArticleData;
use App\Article\Application\Exception\ArticleNotFound;
use App\Article\Application\Service\ArticleService;
use App\Article\Domain\Exception\ArticleInUse;
use App\Article\Domain\Exception\InvalidArticleData;
use App\Article\Domain\Repository\ArticleRepository;
use App\Article\Infrastructure\Http\Dto\ArticleFormViewData;
use App\Article\Infrastructure\Http\Dto\ArticleIndexViewData;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Twig\Environment;

final readonly class AdminArticleController
{
    public function __construct(
        private Environment $twig,
        private ArticleRepository $articles,
        private ArticleService $articleService,
        private CsrfTokenManagerInterface $csrfTokens,
        private UrlGeneratorInterface $urls,
    ) {
    }

    #[Route('/admin/articles', name: 'admin_article_index', methods: [Request::METHOD_GET])]
    public function index(): Response
    {
        $viewData = new ArticleIndexViewData($this->articles->all());

        return new Response($this->twig->render(
            'article/admin/index.html.twig',
            $viewData->toArray(),
        ));
    }

    #[Route(
        '/admin/articles/create',
        name: 'admin_article_create',
        methods: [Request::METHOD_GET, Request::METHOD_POST],
    )]
    public function create(Request $request): Response
    {
        $data = new ArticleData('', '');
        $error = null;

        if ($request->isMethod(Request::METHOD_POST)) {
            $this->assertCsrfToken($request, 'article_create');
            $data = $this->dataFromRequest($request);

            try {
                $this->articleService->create($data);

                return $this->redirectToIndex();
            } catch (InvalidArticleData $exception) {
                $error = $exception->getMessage();
            }
        }

        return $this->renderForm(
            'Nowy artykuł',
            'Dodaj artykuł',
            'article_create',
            $data,
            $error,
        );
    }

    #[Route(
        '/admin/articles/{id}/edit',
        name: 'admin_article_edit',
        requirements: ['id' => '\d+'],
        methods: [Request::METHOD_GET, Request::METHOD_POST],
    )]
    public function edit(int $id, Request $request): Response
    {
        $error = null;

        if ($request->isMethod(Request::METHOD_POST)) {
            $this->assertCsrfToken($request, sprintf('article_edit_%d', $id));
            $data = $this->dataFromRequest($request);

            try {
                $this->articleService->update($id, $data);

                return $this->redirectToIndex();
            } catch (InvalidArticleData $exception) {
                $error = $exception->getMessage();
            } catch (ArticleNotFound $exception) {
                throw new NotFoundHttpException($exception->getMessage(), $exception);
            }
        } else {
            $article = $this->articles->find($id);

            if (null === $article) {
                throw new NotFoundHttpException(sprintf('Artykuł o identyfikatorze %d nie istnieje.', $id));
            }

            $data = ArticleData::fromArticle($article);
        }

        return $this->renderForm(
            'Edycja artykułu',
            'Zapisz zmiany',
            sprintf('article_edit_%d', $id),
            $data,
            $error,
        );
    }

    #[Route(
        '/admin/articles/{id}/delete',
        name: 'admin_article_delete',
        requirements: ['id' => '\\d+'],
        methods: [Request::METHOD_POST],
    )]
    public function delete(int $id, Request $request): Response
    {
        $this->assertCsrfToken($request, sprintf('article_delete_%d', $id));

        try {
            $this->articleService->delete($id);
        } catch (ArticleInUse $exception) {
            $request->getSession()->getFlashBag()->add('error', $exception->getMessage());
        } catch (ArticleNotFound $exception) {
            throw new NotFoundHttpException($exception->getMessage(), $exception);
        }

        return $this->redirectToIndex();
    }

    private function dataFromRequest(Request $request): ArticleData
    {
        return new ArticleData(
            $request->request->getString('name'),
            $request->request->getString('unit_of_measure'),
        );
    }

    private function assertCsrfToken(Request $request, string $tokenId): void
    {
        $token = new CsrfToken($tokenId, $request->request->getString('_csrf_token'));

        if (!$this->csrfTokens->isTokenValid($token)) {
            throw new AccessDeniedHttpException('Token CSRF jest nieprawidłowy.');
        }
    }

    private function renderForm(
        string $heading,
        string $submitLabel,
        string $csrfTokenId,
        ArticleData $data,
        ?string $error,
    ): Response {
        $viewData = new ArticleFormViewData(
            $heading,
            $submitLabel,
            $csrfTokenId,
            $data,
            $error,
        );

        return new Response($this->twig->render(
            'article/admin/form.html.twig',
            $viewData->toArray(),
        ));
    }

    private function redirectToIndex(): RedirectResponse
    {
        return new RedirectResponse(
            $this->urls->generate('admin_article_index'),
            Response::HTTP_SEE_OTHER,
        );
    }
}
