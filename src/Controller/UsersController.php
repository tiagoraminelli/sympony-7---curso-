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

#[Route('/users')]
final class UsersController extends AbstractController
{
    /**
     * Muestra la lista de todos los usuarios
     * @Route("/", name="app_users_index", methods={"GET"})
     * Equivalente a: UserController@index en Laravel
     */
    #[Route(name: 'app_users_index', methods: ['GET'])]
    public function index(UsersRepository $usersRepository): Response
    {
        // similar a: $users = User::all();
        // return view('users.index', compact('users'));

        //$users = $usersRepository->findByName('simpatico');
        $users = $usersRepository->findByEmail('sympony@gmail.com');

        //$users = $usersRepository->findAll();


        return $this->render('users/index.html.twig', [
            'users' => $users,
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
