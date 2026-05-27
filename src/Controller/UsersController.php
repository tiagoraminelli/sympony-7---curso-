<?php

namespace App\Controller;

use App\Entity\Users;
use App\Form\UsersType;
use App\Repository\UsersRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

# composer require knplabs/knp-paginator-bundle
# Dependencia para crear las paginaciones en la vista de index, se puede usar para paginar cualquier consulta a la base de datos, no solo para usuarios
use Knp\Component\Pager\PaginatorInterface;

# para delimitar el número de paginas que se muestran en la vista ir a:
# config/packages/knp_paginator.yaml
// knp_paginator:
//     page_range: 3
//     template:
//         pagination: '@KnpPaginator/Pagination/bootstrap_v5_pagination.html.twig'

#[Route('/users')]
final class UsersController extends AbstractController
{
    /**
     * Muestra la lista de todos los usuarios
     * @Route("/", name="app_users_index", methods={"GET"})
     * Equivalente a: UserController@index en Laravel
     */
    #[Route(name: 'app_users_index', methods: ['GET'])]
    public function index(
        Request $request,
        UsersRepository $usersRepository,
        PaginatorInterface $paginator
    ): Response {

        $search = $request->query->get('search');

        $query = $usersRepository
            ->createQueryBuilder('u');

        if ($search) {

            $query
                ->where('u.name LIKE :search')
                ->orWhere('u.email LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        $query->orderBy('u.id', 'DESC');

        // similar a: $users = User::paginate(10);

        // El método paginate recibe la consulta, el número de página actual y el número de resultados por página, y devuelve un objeto de paginación que se puede usar en la vista para mostrar los usuarios paginados
        $users = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            5
        );

        return $this->render('users/index.html.twig', [
            'users' => $users,
            'search' => $search,
        ]);
    }

    /**
     * Muestra el formulario para crear un nuevo usuario y lo guarda en la BD
     * @Route("/new", name="app_users_new", methods={"GET", "POST"})
     * Equivalente a: UserController@create y UserController@store en Laravel
     */
    #[Route('/new', name: 'app_users_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        // similar a: $user = new User();
        $user = new Users();

        // similar a: $form = $this->createForm(UserType::class, $user);
        $form = $this->createForm(UsersType::class, $user);

        // similar a: $form->handleRequest($request);
        $form->handleRequest($request);

        // similar a: if ($form->isSubmitted() && $form->isValid())
        if ($form->isSubmitted() && $form->isValid()) {
            // similar a: $user->save()
            $entityManager->persist($user);
            $entityManager->flush();

            // similar a: return redirect()->route('users.index');
            return $this->redirectToRoute('app_users_index', [], Response::HTTP_SEE_OTHER);
        }

        // similar a: return view('users.create', compact('form'));
        return $this->render('users/new.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    /**
     * Muestra los detalles de un usuario específico
     * @Route("/{id}", name="app_users_show", methods={"GET"})
     * Equivalente a: UserController@show en Laravel
     */
    #[Route('/{id}', name: 'app_users_show', methods: ['GET'])]
    public function show(Users $user): Response
    {
        // similar a: return view('users.show', compact('user'));
        return $this->render('users/show.html.twig', [
            'user' => $user,
        ]);
    }

    /**
     * Muestra el formulario para editar un usuario y actualiza sus datos
     * @Route("/{id}/edit", name="app_users_edit", methods={"GET", "POST"})
     * Equivalente a: UserController@edit y UserController@update en Laravel
     */
    #[Route('/{id}/edit', name: 'app_users_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Users $user, EntityManagerInterface $entityManager): Response
    {
        // similar a: $form = $this->createForm(UserType::class, $user);
        $form = $this->createForm(UsersType::class, $user);

        // similar a: $form->handleRequest($request);
        $form->handleRequest($request);

        // similar a: if ($form->isSubmitted() && $form->isValid())
        if ($form->isSubmitted() && $form->isValid()) {
            // similar a: $user->save()
            $entityManager->flush();

            // similar a: return redirect()->route('users.index');
            return $this->redirectToRoute('app_users_index', [], Response::HTTP_SEE_OTHER);
        }

        // similar a: return view('users.edit', compact('user', 'form'));
        return $this->render('users/edit.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    /**
     * Elimina un usuario de la base de datos
     * @Route("/{id}", name="app_users_delete", methods={"POST"})
     * Equivalente a: UserController@destroy en Laravel
     */
    #[Route('/{id}', name: 'app_users_delete', methods: ['POST'])]
    public function delete(Request $request, Users $user, EntityManagerInterface $entityManager): Response
    {
        // similar a: @csrf en Laravel, verifica el token CSRF
        if ($this->isCsrfTokenValid('delete' . $user->getId(), $request->getPayload()->getString('_token'))) {
            // similar a: $user->delete()
            $entityManager->remove($user);
            $entityManager->flush();
        }

        // similar a: return redirect()->route('users.index');
        return $this->redirectToRoute('app_users_index', [], Response::HTTP_SEE_OTHER);
    }
}
