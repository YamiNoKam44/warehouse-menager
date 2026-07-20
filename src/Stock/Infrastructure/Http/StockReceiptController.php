<?php

declare(strict_types=1);

namespace App\Stock\Infrastructure\Http;

use App\Article\Application\Exception\ArticleNotFound;
use App\Identity\Application\Exception\UserNotFound;
use App\Identity\Infrastructure\Security\SecurityUser;
use App\Stock\Application\Exception\CannotStoreReceiptDocument;
use App\Stock\Application\Exception\StockReceiptAccessDenied;
use App\Stock\Application\Service\StockReceiptService;
use App\Stock\Domain\Exception\InvalidStockQuantity;
use App\Stock\Domain\Exception\InvalidStockReceiptData;
use App\Stock\Infrastructure\Http\Form\StockReceiptFormData;
use App\Stock\Infrastructure\Http\Form\StockReceiptType;
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

final readonly class StockReceiptController
{
    public function __construct(
        private Environment $twig,
        private Security $security,
        private StockReceiptService $receiptService,
        private FormFactoryInterface $forms,
        private UrlGeneratorInterface $urls,
    ) {
    }

    #[Route('/stock/receipts/create', name: 'app_stock_receipt_create', methods: ['GET', 'POST'])]
    public function __invoke(Request $request): Response
    {
        $user = $this->security->getUser();

        if (!$user instanceof SecurityUser) {
            throw new AccessDeniedException();
        }

        try {
            $warehouses = $this->receiptService->availableWarehouses($user->id());
        } catch (UserNotFound $exception) {
            throw new AccessDeniedException('Brak dostępu.', $exception);
        }

        $data = new StockReceiptFormData();
        $form = $this->forms->create(StockReceiptType::class, $data, [
            'action' => $this->urls->generate('app_stock_receipt_create'),
            'method' => 'POST',
            'warehouses' => $warehouses,
            'articles' => $this->receiptService->availableArticles(),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->receiptService->receive($user->id(), $data->toReceiptData());

                return new RedirectResponse(
                    $this->urls->generate('app_stock_receipt_create', ['saved' => 1]),
                    Response::HTTP_SEE_OTHER,
                );
            } catch (StockReceiptAccessDenied|UserNotFound $exception) {
                throw new AccessDeniedException('Brak dostępu.', $exception);
            } catch (
                InvalidStockReceiptData
                |InvalidStockQuantity
                |CannotStoreReceiptDocument
                |ArticleNotFound
                |WarehouseNotFound $exception
            ) {
                $form->addError(new FormError($exception->getMessage()));
            }
        }

        return new Response($this->twig->render('stock/receipt/create.html.twig', [
            'form' => $form->createView(),
            'saved' => $request->query->getBoolean('saved'),
        ]));
    }
}
