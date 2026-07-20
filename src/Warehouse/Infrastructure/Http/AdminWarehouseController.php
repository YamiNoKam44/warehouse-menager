<?php

declare(strict_types=1);

namespace App\Warehouse\Infrastructure\Http;

use App\Warehouse\Application\Exception\WarehouseNotFound;
use App\Warehouse\Application\Service\WarehouseService;
use App\Warehouse\Domain\Exception\InvalidWarehouseData;
use App\Warehouse\Domain\Exception\WarehouseInUse;
use App\Warehouse\Domain\Repository\WarehouseRepository;
use App\Warehouse\Infrastructure\Http\Dto\WarehouseFormViewData;
use App\Warehouse\Infrastructure\Http\Dto\WarehouseIndexViewData;
use App\Warehouse\Infrastructure\Http\Form\WarehouseFormData;
use App\Warehouse\Infrastructure\Http\Form\WarehouseType;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
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

final readonly class AdminWarehouseController
{
    public function __construct(
        private Environment $twig,
        private WarehouseRepository $warehouses,
        private WarehouseService $warehouseService,
        private FormFactoryInterface $forms,
        private CsrfTokenManagerInterface $csrfTokens,
        private UrlGeneratorInterface $urls,
    ) {
    }

    #[Route('/admin/warehouses', name: 'admin_warehouse_index', methods: [Request::METHOD_GET])]
    public function index(): Response
    {
        $viewData = new WarehouseIndexViewData($this->warehouses->all());

        return new Response($this->twig->render(
            'warehouse/admin/index.html.twig',
            $viewData->toArray(),
        ));
    }

    #[Route(
        '/admin/warehouses/create',
        name: 'admin_warehouse_create',
        methods: [Request::METHOD_GET, Request::METHOD_POST],
    )]
    public function create(Request $request): Response
    {
        $data = new WarehouseFormData();
        $form = $this->createForm($data, 'admin_warehouse_create');
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->warehouseService->create($data->toWarehouseData());

                return $this->redirectToIndex();
            } catch (InvalidWarehouseData $exception) {
                $form->addError(new FormError($exception->getMessage()));
            }
        }

        return $this->renderForm(
            'Nowy magazyn',
            'Dodaj magazyn',
            $form->createView(),
        );
    }

    #[Route(
        '/admin/warehouses/{id}/edit',
        name: 'admin_warehouse_edit',
        requirements: ['id' => '\d+'],
        methods: [Request::METHOD_GET, Request::METHOD_POST],
    )]
    public function edit(int $id, Request $request): Response
    {
        $data = $request->isMethod(Request::METHOD_POST)
            ? new WarehouseFormData()
            : $this->formDataFor($id);
        $form = $this->createForm($data, 'admin_warehouse_edit', $id);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->warehouseService->update($id, $data->toWarehouseData());

                return $this->redirectToIndex();
            } catch (InvalidWarehouseData $exception) {
                $form->addError(new FormError($exception->getMessage()));
            } catch (WarehouseNotFound $exception) {
                throw new NotFoundHttpException($exception->getMessage(), $exception);
            }
        }

        return $this->renderForm(
            'Edycja magazynu',
            'Zapisz zmiany',
            $form->createView(),
        );
    }

    #[Route(
        '/admin/warehouses/{id}/delete',
        name: 'admin_warehouse_delete',
        requirements: ['id' => '\d+'],
        methods: [Request::METHOD_POST],
    )]
    public function delete(int $id, Request $request): Response
    {
        $this->assertCsrfToken($request, sprintf('warehouse_delete_%d', $id));

        try {
            $this->warehouseService->delete($id);
        } catch (WarehouseInUse $exception) {
            $request->getSession()->getFlashBag()->add('error', $exception->getMessage());
        } catch (WarehouseNotFound $exception) {
            throw new NotFoundHttpException($exception->getMessage(), $exception);
        }

        return $this->redirectToIndex();
    }

    private function createForm(
        WarehouseFormData $data,
        string $route,
        ?int $id = null,
    ): FormInterface {
        $routeParameters = null === $id ? [] : ['id' => $id];

        return $this->forms->create(WarehouseType::class, $data, [
            'action' => $this->urls->generate($route, $routeParameters),
            'method' => Request::METHOD_POST,
        ]);
    }

    private function formDataFor(int $id): WarehouseFormData
    {
        $warehouse = $this->warehouses->find($id);

        if (null === $warehouse) {
            throw new NotFoundHttpException(sprintf('Magazyn o identyfikatorze %d nie istnieje.', $id));
        }

        return WarehouseFormData::fromWarehouse($warehouse);
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
        FormView $form,
    ): Response {
        $viewData = new WarehouseFormViewData($heading, $submitLabel, $form);

        return new Response($this->twig->render(
            'warehouse/admin/form.html.twig',
            $viewData->toArray(),
        ));
    }

    private function redirectToIndex(): RedirectResponse
    {
        return new RedirectResponse(
            $this->urls->generate('admin_warehouse_index'),
            Response::HTTP_SEE_OTHER,
        );
    }
}
