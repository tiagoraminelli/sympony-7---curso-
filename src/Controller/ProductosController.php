<?php

namespace App\Controller;

use App\Entity\Productos;

use App\Form\ProductosType;
use App\Repository\ProductosRepository;
use App\Repository\CategoriaRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;


#[Route('/productos')]
final class ProductosController extends AbstractController
{
    #[Route(name: 'app_productos_index', methods: ['GET'])]
    public function index(
        Request $request,
        ProductosRepository $productosRepository,
        CategoriaRepository $categoriaRepository,
        PaginatorInterface $paginator
    ): Response {
        $search = $request->query->get('search');
        $categoriaId = $request->query->get('categoria');

        $queryBuilder = $productosRepository->createQueryBuilder('p');

        if ($search) {
            $queryBuilder
                ->where('p.nombre LIKE :search')
                ->orWhere('p.descripcion LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        if ($categoriaId && $categoriaId !== '') {
            $queryBuilder
                ->andWhere('p.categoria = :categoriaId')
                ->setParameter('categoriaId', $categoriaId);
        }

        $queryBuilder->orderBy('p.id', 'DESC');

        $productos = $paginator->paginate(
            $queryBuilder,
            $request->query->getInt('page', 1),
            5
        );

        // Obtener todas las categorías para el select
        $categorias = $categoriaRepository->findAll();

        $breadcrumbs = [
            ['label' => 'Productos', 'url' => '']
        ];

        return $this->render('productos/index.html.twig', [
            'productos' => $productos,
            'breadcrumbs' => $breadcrumbs,
            'search' => $search,
            'categoriaId' => $categoriaId,
            'categorias' => $categorias,
        ]);
    }

    #[Route('/new', name: 'app_productos_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $producto = new Productos();
        $form = $this->createForm(ProductosType::class, $producto);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($producto);
            $entityManager->flush();

            return $this->redirectToRoute('app_productos_index', [], Response::HTTP_SEE_OTHER);
        }

        $breadcrumbs = [
            ['label' => 'Productos', 'url' => $this->generateUrl('app_productos_index')],
            ['label' => 'Crear Producto', 'url' => '']
        ];

        return $this->render('productos/new.html.twig', [
            'producto' => $producto,
            'form' => $form,
            'breadcrumbs' => $breadcrumbs,
        ]);
    }

    #[Route('/{id}', name: 'app_productos_show', methods: ['GET'])]
    public function show(Productos $producto): Response
    {
        $breadcrumbs = [
            ['label' => 'Productos', 'url' => $this->generateUrl('app_productos_index')],
            ['label' => 'Ver: ' . ($producto->getNombre() ?? $producto->getId()), 'url' => '']
        ];

        return $this->render('productos/show.html.twig', [
            'producto' => $producto,
            'breadcrumbs' => $breadcrumbs,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_productos_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Productos $producto, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ProductosType::class, $producto);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_productos_index', [], Response::HTTP_SEE_OTHER);
        }

        $breadcrumbs = [
            ['label' => 'Productos', 'url' => $this->generateUrl('app_productos_index')],
            ['label' => 'Editar: ' . ($producto->getNombre() ?? $producto->getId()), 'url' => '']
        ];

        return $this->render('productos/edit.html.twig', [
            'producto' => $producto,
            'form' => $form,
            'breadcrumbs' => $breadcrumbs,
        ]);
    }

    #[Route('/{id}', name: 'app_productos_delete', methods: ['POST'])]
    public function delete(Request $request, Productos $producto, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $producto->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($producto);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_productos_index', [], Response::HTTP_SEE_OTHER);
    }
}
