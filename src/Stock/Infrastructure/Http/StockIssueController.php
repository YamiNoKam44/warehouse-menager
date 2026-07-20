<?php

declare(strict_types=1);

namespace App\Stock\Infrastructure\Http;

use App\Article\Application\Exception\ArticleNotFound;
use App\Identity\Application\Exception\UserNotFound;
use App\Identity\Infrastructure\Security\SecurityUser;
use App\Stock\Application\Exception\StockIssueAccessDenied;
use App\Stock\Application\Service\StockIssueService;
use App\Stock\Domain\Exception\InvalidStockQuantity;
use App\Stock\Infrastructure\Http\Dto\StockOperationFormViewData;
use App\Stock\Infrastructure\Http\Form\StockIssueFormData;
use App\Stock\Infrastructure\Http\Form\StockIssueType;
use App\Warehouse\Application\Exception\WarehouseNotFound;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Twig\Environment;

final readonly class StockIssueController
{
    public function __construct(
        private Environment $twig,
        private Security $security,
        private StockIssueService $issueService,
        private FormFactoryInterface $forms,
        private UrlGeneratorInterface $urls,
    ) {
    }

    #[Route(
        '/stock/issues/create',
        name: 'app_stock_issue_create',
        methods: [Request::METHOD_GET, Request::METHOD_POST],
    )]
    public function __invoke(Request $request): Response
    {
        $user = $this->security->getUser();

        if (!$user instanceof SecurityUser) {
            throw new AccessDeniedException();
        }

        try {
            $warehouses = $this->issueService->availableWarehouses($user->id());
        } catch (UserNotFound $exception) {
            throw new AccessDeniedException('Brak dostępu.', $exception);
        }

        $data = new StockIssueFormData();
        $form = $this->forms->create(StockIssueType::class, $data, [
            'action' => $this->urls->generate('app_stock_issue_create'),
            'method' => Request::METHOD_POST,
            'warehouses' => $warehouses,
            'articles' => $this->issueService->availableArticles(),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->issueService->issue($user->id(), $data->toIssueData());

                return new RedirectResponse(
                    $this->urls->generate('app_stock_issue_create', ['saved' => 1]),
                    Response::HTTP_SEE_OTHER,
                );
            } catch (StockIssueAccessDenied|UserNotFound $exception) {
                throw new AccessDeniedException('Brak dostępu.', $exception);
            } catch (
                InvalidStockQuantity
                |ArticleNotFound
                |WarehouseNotFound $exception
            ) {
                $form->addError(new FormError($exception->getMessage()));
            }
        }

        $viewData = new StockOperationFormViewData(
            $form->createView(),
            $request->query->getBoolean('saved'),
        );

        return new Response($this->twig->render(
            'stock/issue/create.html.twig',
            $viewData->toArray(),
        ));
    }
}
