<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\Http;

use App\Identity\Application\Exception\InvalidPassword;
use App\Identity\Application\Exception\UserNotFound;
use App\Identity\Application\Service\UserService;
use App\Identity\Domain\Exception\InvalidLogin;
use App\Identity\Domain\Exception\UserAlreadyExists;
use App\Identity\Domain\Repository\UserRepository;
use App\Identity\Infrastructure\Http\Dto\UserFormViewData;
use App\Identity\Infrastructure\Http\Dto\UserIndexViewData;
use App\Identity\Infrastructure\Http\Form\UserFormData;
use App\Identity\Infrastructure\Http\Form\UserType;
use App\Warehouse\Application\Exception\WarehouseNotFound;
use App\Warehouse\Domain\Repository\WarehouseRepository;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;

final readonly class AdminUserController
{
    public function __construct(
        private Environment $twig,
        private UserRepository $users,
        private WarehouseRepository $warehouses,
        private UserService $userService,
        private FormFactoryInterface $forms,
        private UrlGeneratorInterface $urls,
    ) {
    }

    #[Route('/admin/users', name: 'admin_user_index', methods: [Request::METHOD_GET])]
    public function index(): Response
    {
        $viewData = new UserIndexViewData($this->users->all());

        return new Response($this->twig->render(
            'identity/admin/index.html.twig',
            $viewData->toArray(),
        ));
    }

    #[Route(
        '/admin/users/create',
        name: 'admin_user_create',
        methods: [Request::METHOD_GET, Request::METHOD_POST],
    )]
    public function create(Request $request): Response
    {
        $data = new UserFormData();
        $form = $this->createForm($data, 'admin_user_create', true);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->userService->create($data->toUserData());

                return $this->redirectToIndex();
            } catch (InvalidLogin|InvalidPassword|UserAlreadyExists|WarehouseNotFound $exception) {
                $form->addError(new FormError($exception->getMessage()));
            }
        }

        return $this->renderForm('Nowy użytkownik', 'Dodaj użytkownika', true, $form->createView());
    }

    #[Route(
        '/admin/users/{id}/edit',
        name: 'admin_user_edit',
        requirements: ['id' => '\d+'],
        methods: [Request::METHOD_GET, Request::METHOD_POST],
    )]
    public function edit(int $id, Request $request): Response
    {
        $data = $request->isMethod(Request::METHOD_POST)
            ? new UserFormData()
            : $this->formDataFor($id);
        $form = $this->createForm($data, 'admin_user_edit', false, $id);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->userService->update($id, $data->toUserData());

                return $this->redirectToIndex();
            } catch (InvalidLogin|InvalidPassword|UserAlreadyExists|WarehouseNotFound $exception) {
                $form->addError(new FormError($exception->getMessage()));
            } catch (UserNotFound $exception) {
                throw new NotFoundHttpException($exception->getMessage(), $exception);
            }
        }

        return $this->renderForm('Edycja użytkownika', 'Zapisz zmiany', false, $form->createView());
    }

    private function createForm(
        UserFormData $data,
        string $route,
        bool $passwordRequired,
        ?int $id = null,
    ): FormInterface {
        return $this->forms->create(UserType::class, $data, [
            'action' => $this->urls->generate($route, null === $id ? [] : ['id' => $id]),
            'method' => Request::METHOD_POST,
            'password_required' => $passwordRequired,
        ]);
    }

    private function formDataFor(int $id): UserFormData
    {
        $user = $this->users->find($id);

        if (null === $user) {
            throw new NotFoundHttpException(sprintf('Użytkownik o identyfikatorze %d nie istnieje.', $id));
        }

        return UserFormData::fromUser($user, $this->warehouses->assignedToUser($id));
    }

    private function renderForm(
        string $heading,
        string $submitLabel,
        bool $passwordRequired,
        FormView $form,
    ): Response {
        $viewData = new UserFormViewData($heading, $submitLabel, $passwordRequired, $form);

        return new Response($this->twig->render(
            'identity/admin/form.html.twig',
            $viewData->toArray(),
        ));
    }

    private function redirectToIndex(): RedirectResponse
    {
        return new RedirectResponse(
            $this->urls->generate('admin_user_index'),
            Response::HTTP_SEE_OTHER,
        );
    }
}
