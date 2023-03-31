<?php

namespace App\Controller\Admin;

use App\Entity\Category;
use App\Enum\ColorTypeEnum;
use App\Form\Admin\CategoryType;
use App\Manager\FlashManager;
use App\Repository\CategoryRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\UX\Turbo\TurboBundle;

#[Route('/category')]
final class CategoryController extends AbstractController
{
    public function __construct(
        private readonly FlashManager $flashManager,
        private readonly CategoryRepository $categoryRepository,
        private readonly CsrfTokenManagerInterface $csrfTokenManager
    ) {
    }

    #[Route('/index', name: 'admin_category', methods: ['GET'])]
    public function index(PaginatorInterface $paginator, Request $request): Response
    {
        $pagination = $paginator->paginate(
            $this->categoryRepository->createQueryBuilder('c'),
            $request->query->getInt('page', 1),
            5
        );

        $config = [
          'cols' => [
                'name' => [
                    'type' => 'text',
                    'label' => 'category.name',
                    'queryKey' => 'c.name',
                ],
                'actions' => [
                    'info' => [
                        'route' => 'admin_category_show',
                        'routeParams' => [
                            'id' => static fn (Category $category): string => $category->getId()->toBase32(),
                        ],
                        'icon' => 'eye',
                    ],
                    'accent' => [
                        'route' => 'admin_category_edit',
                        'routeParams' => [
                            'id' => static fn (Category $category): string => $category->getId()->toBase32(),
                        ],
                        'icon' => 'pencil',
                    ],
                ],
            ],
            'pagination' => $pagination,
        ];

        return $this->render('admin/category/index.html.twig', ['config' => $config]);
    }

    #[Route('/new', name: 'admin_category_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $category = new Category();
        $form = $this->createForm(CategoryType::class, $category)->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $this->categoryRepository->save($category, true);

                $this->flashManager->flash('success', 'flash.new.success', translationDomain: 'admin');

                return $this->redirectToRoute('admin_category_edit', [
                    'id' => $category->getId(),
                ], Response::HTTP_SEE_OTHER);
            }
            $this->flashManager->flash('error', 'flash.form.error', translationDomain: 'admin');

            if (TurboBundle::STREAM_FORMAT === $request->getPreferredFormat()) {
                $request->setRequestFormat(TurboBundle::STREAM_FORMAT);

                return $this->render('common/stream/form.stream.html.twig', [
                    'form_view' => 'admin/category/_form.html.twig',
                    'form' => $form,
                    'target' => 'category-admin-form',
                ]);
            }
        }

        return $this->render('admin/category/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'admin_category_show', methods: ['GET'])]
    public function show(Category $category): Response
    {
        return $this->render('admin/category/show.html.twig', [
            'category' => $category,
        ]);
    }

    #[Route('/edit/{id}', name: 'admin_category_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Category $category): Response
    {
        $form = $this->createForm(CategoryType::class, $category)->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $this->categoryRepository->save($category, true);

                $this->flashManager->flash(ColorTypeEnum::Success->value, 'flash.common.updated', translationDomain: 'admin');
            } else {
                $this->flashManager->flash(ColorTypeEnum::Error->value, 'flash.common.invalid_form');
            }

            if (TurboBundle::STREAM_FORMAT === $request->getPreferredFormat()) {
                $request->setRequestFormat(TurboBundle::STREAM_FORMAT);

                return $this->render('common/stream/form.stream.html.twig', [
                    'form_view' => 'admin/category/_form.html.twig',
                    'form' => $form,
                    'target' => 'category-admin-form',
                ]);
            } elseif ($form->isValid()) {
                return $this->redirectToRoute('admin_category_edit', [
                    'id' => $category->getId()->toBase32(),
                ], Response::HTTP_SEE_OTHER);
            }
        }

        return $this->render('admin/category/edit.html.twig', [
            'category' => $category,
            'form' => $form,
        ]);
    }

    #[Route('/delete/{id}', name: 'admin_category_delete', methods: ['POST'])]
    public function delete(Request $request, Category $category): Response
    {
        if ($this->isCsrfTokenValid('delete-'.$category->getId()->toBase32(), $request->request->get('_token'))) {
            $this->categoryRepository->remove($category, true);

            $this->flashManager->flash(ColorTypeEnum::Success->value, 'flash.common.deleted', translationDomain: 'admin');
        } else {
            $this->flashManager->flash(ColorTypeEnum::Error->value, 'flash.common.invalid_csrf');
        }

        return $this->redirectToRoute('admin_category', status: Response::HTTP_SEE_OTHER);
    }
}
