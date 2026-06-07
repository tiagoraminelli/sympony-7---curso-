php bin/console make:controller HomeController

Esto te va a generar:

src/Controller/HomeController.php
templates/home/index.html.twig

Luego editás `src/Controller/HomeController.php` y cambiás la ruta que genera

php bin/console debug:router app_home  Para comprobar que la ruta quedó registrada:

+--------------+---------------------------------------------------------+
| Property     | Value                                                   |
+--------------+---------------------------------------------------------+
| Route Name   | app_home                                                |
| Path         | /                                                       |
| Path Regex   | {^/$}sDu                                                |
| Host         | ANY                                                     |
| Host Regex   |                                                         |
| Scheme       | ANY                                                     |
| Method       | ANY                                                     |
| Requirements | NO CUSTOM                                               |
| Class        | Symfony\Component\Routing\Route                         |
| Defaults     | _controller: App\Controller\HomeController::index()     |
| Options      | compiler_class: Symfony\Component\Routing\RouteCompiler |
|              | utf8: true                                              |
+--------------+---------------------------------------------------------+
PS C:\xampp\htdocs\my_project>


## 1. Crear la entidad Proveedores

php bin/console make:entity Proveedores

Luego ingresa los siguientes campos en el asistente:

nombre (string, length=150, nullable=false)
razon_social (string, length=200, nullable=true)
cuit (string, length=20, nullable=true)
telefono (string, length=50, nullable=true)
email (string, length=100, nullable=true)
direccion (text, nullable=true)
ciudad (string, length=100, nullable=true)
provincia (string, length=100, nullable=true)
codigo_postal (string, length=20, nullable=true)
contacto (string, length=100, nullable=true)
notas (text, nullable=true)
activo (boolean, nullable=true)
created_at (datetime_immutable, nullable=true)
updated_at (datetime_immutable, nullable=true)


## 2. Generar el CRUD completo

php bin/console make:crud Proveedores


php bin/console make:migration
