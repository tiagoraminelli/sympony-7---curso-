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

    #[Route('/bulk-fetch', name: 'app_productos_bulk_fetch', methods: ['GET'])]
    public function bulkFetch(
        Request $request,
        ProductosRepository $productosRepository
    ): Response {
        $search = $request->query->get('search');
        $categoriaId = $request->query->get('categoria');

        $qb = $productosRepository->createQueryBuilder('p')
            ->leftJoin('p.categoria', 'c')
            ->addSelect('c');

        if ($search) {
            $qb->andWhere('p.nombre LIKE :search OR p.descripcion LIKE :search')
                ->setParameter('search', "%{$search}%");
        }

        if ($categoriaId && $categoriaId !== '') {
            $qb->andWhere('p.categoria = :categoria')
                ->setParameter('categoria', $categoriaId);
        }

        $productos = $qb->orderBy('p.id', 'DESC')->getQuery()->getResult();

        $data = [];
        foreach ($productos as $p) {
            $data[] = [
                'id' => $p->getId(),
                'nombre' => $p->getNombre(),
                'descripcion' => $p->getDescripcion(),
                'categoria' => $p->getCategoria() ? $p->getCategoria()->getNombre() : null,
                'stock' => $p->getStock(),
                'precioCosto' => $p->getPrecioCosto(),
                'precio' => $p->getPrecio(),
                'activo' => $p->isActivo(),
            ];
        }

        return $this->json(['productos' => $data]);
    }

    #[Route('/bulk-update', name: 'app_productos_bulk_update', methods: ['GET'])]
    public function bulkUpdate(
        Request $request,
        ProductosRepository $productosRepository,
        CategoriaRepository $categoriaRepository
    ): Response {
        $search = $request->query->get('search');
        $categoriaId = $request->query->get('categoria');

        $queryBuilder = $productosRepository->createQueryBuilder('p')
            ->leftJoin('p.categoria', 'c')
            ->addSelect('c');

        if ($search) {
            $queryBuilder
                ->andWhere('p.nombre LIKE :search OR p.descripcion LIKE :search')
                ->setParameter('search', "%{$search}%");
        }

        if ($categoriaId && $categoriaId !== '') {
            $queryBuilder
                ->andWhere('p.categoria = :categoria')
                ->setParameter('categoria', $categoriaId);
        }

        $productos = $queryBuilder->orderBy('p.id', 'DESC')->getQuery()->getResult();
        $categorias = $categoriaRepository->findAll();

        return $this->render('productos/bulk_update.html.twig', [
            'productos' => $productos,
            'categorias' => $categorias,
            'search' => $search,
            'categoriaId' => $categoriaId,
            'breadcrumbs' => [
                ['label' => 'Productos', 'url' => $this->generateUrl('app_productos_index')],
                ['label' => 'Actualización Masiva', 'url' => '']
            ],
        ]);
    }

    #[Route('/bulk-update-categoria', name: 'app_productos_bulk_update_categoria', methods: ['POST'])]
    public function bulkUpdateCategoria(
        Request $request,
        ProductosRepository $productosRepository,
        CategoriaRepository $categoriaRepository,
        EntityManagerInterface $entityManager
    ): Response {
        if (!$this->isCsrfTokenValid('bulk_update_categoria', $request->request->get('_token'))) {
            $this->addFlash('danger', 'Token inválido');
            return $this->redirectToRoute('app_productos_bulk_update');
        }

        $categoriaId = $request->request->get('categoria_id');
        $tipoAccion = $request->request->get('tipo_accion');
        $valor = $request->request->get('valor');

        if (!$categoriaId) {
            $this->addFlash('danger', 'Debe seleccionar una categoría');
            return $this->redirectToRoute('app_productos_bulk_update');
        }

        $productos = $productosRepository->createQueryBuilder('p')
            ->where('p.categoria = :categoria')
            ->setParameter('categoria', $categoriaId)
            ->getQuery()
            ->getResult();

        if (count($productos) === 0) {
            $this->addFlash('warning', 'No hay productos en esta categoría');
            return $this->redirectToRoute('app_productos_bulk_update');
        }

        $contador = 0;

        foreach ($productos as $producto) {
            switch ($tipoAccion) {
                case 'precio_porcentaje':
                    $producto->setPrecio($producto->getPrecio() * (1 + floatval($valor) / 100));
                    break;
                case 'precio_descuento':
                    $producto->setPrecio($producto->getPrecio() * (1 - floatval($valor) / 100));
                    break;
                case 'precio_fijo':
                    $producto->setPrecio(floatval($valor));
                    break;
                case 'precio_incremento_fijo':
                    $producto->setPrecio($producto->getPrecio() + floatval($valor));
                    break;
                case 'precio_decremento_fijo':
                    $producto->setPrecio($producto->getPrecio() - floatval($valor));
                    break;
                case 'stock_incrementar':
                    $producto->setStock($producto->getStock() + floatval($valor));
                    break;
                case 'stock_decrementar':
                    $producto->setStock($producto->getStock() - floatval($valor));
                    break;
                case 'stock_fijo':
                    $producto->setStock(floatval($valor));
                    break;
                case 'estado_activar':
                    $producto->setActivo(true);
                    break;
                case 'estado_desactivar':
                    $producto->setActivo(false);
                    break;
            }
            $contador++;
        }

        $entityManager->flush();

        $this->addFlash('success', "{$contador} productos actualizados correctamente");
        return $this->redirectToRoute('app_productos_bulk_update');
    }

    #[Route('/bulk-update-individual', name: 'app_productos_bulk_update_individual', methods: ['POST'])]
    public function bulkUpdateIndividual(
        Request $request,
        ProductosRepository $productosRepository,
        EntityManagerInterface $entityManager
    ): Response {
        if (!$this->isCsrfTokenValid('bulk_update_individual', $request->request->get('_token'))) {
            if ($request->isXmlHttpRequest()) {
                return $this->json(['success' => false, 'message' => 'Token inválido']);
            }
            $this->addFlash('danger', 'Token inválido');
            return $this->redirectToRoute('app_productos_bulk_update');
        }

        $ids = $request->request->all('id');
        $stocks = $request->request->all('stock');
        $preciosCosto = $request->request->all('precio_costo');
        $precios = $request->request->all('precio');
        $activos = $request->request->all('activo');

        $contador = 0;

        foreach ($ids as $index => $id) {
            $producto = $productosRepository->find($id);

            if ($producto) {
                if (isset($stocks[$id]) && $stocks[$id] !== '') {
                    $producto->setStock((float) $stocks[$id]);
                }

                if (isset($preciosCosto[$id]) && $preciosCosto[$id] !== '') {
                    $producto->setPrecioCosto((float) $preciosCosto[$id]);
                }

                if (isset($precios[$id]) && $precios[$id] !== '') {
                    $producto->setPrecio((float) $precios[$id]);
                }

                $producto->setActivo(isset($activos[$id]));

                $contador++;
            }
        }

        $entityManager->flush();

        if ($request->isXmlHttpRequest()) {
            return $this->json(['success' => true, 'message' => "{$contador} productos actualizados correctamente"]);
        }

        $this->addFlash('success', "{$contador} productos actualizados correctamente");
        return $this->redirectToRoute('app_productos_bulk_update');
    }

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

        #Por defecto el producto se crea como activo
        $producto->setActivo(true);

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
        $token = $request->getPayload()->getString('_token');

        if ($this->isCsrfTokenValid('delete' . $producto->getId(), $token)) {
            try {
                $entityManager->remove($producto);
                $entityManager->flush();
                $this->addFlash('success', 'Producto eliminado correctamente');
            } catch (\Exception $e) {
                $this->addFlash('danger', 'Error al eliminar: ' . $e->getMessage());
            }
        } else {
            $this->addFlash('danger', 'Token CSRF inválido');
        }

        return $this->redirectToRoute('app_productos_index', [], Response::HTTP_SEE_OTHER);
    }
}
