<?php

namespace Blog\Twig\Controller\User;

use Blog\Twig\Controller\CoreController;
use Blog\Twig\Repository\UserRepository;
use Blog\Twig\Utils\Validator;
use Blog\Twig\Utils\Notification;
use Blog\Twig\Model\User;
use PDO;

class UserController extends CoreController
{
    private $userRepository;

    public function __construct($twig, PDO $db)
    {
        parent::__construct($twig, $db);
        $this->userRepository = new UserRepository($db);
    }


    public function userLogin()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = $_POST['username'];
            $password = $_POST['password'];

            // Vérification de l'utilisateur en db via le UserRepository
            $user = $this->userRepository->findByUsername($username);

            // Vérification du mot de passe
            if ($user && password_verify($password, $user->getPassword())) {
                // Démarrage de la session
                session_start();
                $_SESSION['id_user'] = $user->getId();
                $_SESSION['username'] = $user->getUsername();
                $_SESSION['isAdmin'] = $user->getIsAdmin(); // Stocker le statut d'admin

                header('Location: /BlogPHP/home');
                exit;
            } else {
                // Ajout d'une notification d'erreur
                Notification::addMessage(Notification::ERROR, 'Nom d\'utilisateur ou mot de passe incorrect');
            }
        }

        // Affichage du formulaire de connexion avec les notifications
        $notifications = Notification::getMessages();

        echo $this->twig->render('User/userLogin.html.twig', [
            'notifications' => $notifications
        ]);
    }

    public function userRegister()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $name = $_POST['name'];
            $surname = $_POST['surname'];
            $username = $_POST['username'];
            $email = $_POST['email'];
            $password = $_POST['password'];

            $errors = [];

            if (!Validator::validateNameAndSurname($name)) {
                $errors['name'] = 'Le prénom ne peut contenir que des lettres, des espaces, des apostrophes et des tirets';
            }

            if (!Validator::validateNameAndSurname($surname)) {
                $errors['surname'] = 'Le nom ne peut contenir que des lettres, des espaces, des apostrophes et des tirets';
            }

            if (!Validator::validateUsername($username)) {
                $errors['username'] = 'Le nom d\'utilisateur ne peut contenir que des lettres, des chiffres et des underscores (_)';
            }

            if (!Validator::validateEmail($email)) {
                $errors['email'] = 'Veuillez entrer une adresse e-mail valide';
            }

            if (!Validator::validatePassword($password)) {
                $errors['password'] = 'Le mot de passe doit contenir au moins 8 caractères avec au moins une majuscule, un chiffre et un caractère spécial';
            }

            if ($this->userRepository->findByUsername($username)) {
                $errors['username'] = 'Ce nom d\'utilisateur est déjà pris';
            }

            if ($this->userRepository->findByEmail($email)) {
                $errors['email'] = 'Cette adresse e-mail est déjà utilisée';
            }

            if (empty($errors)) {
                $user = new User(
                    null,
                    $name,
                    strtoupper($surname),
                    $username,
                    $email,
                    password_hash($password, PASSWORD_BCRYPT),
                    0
                );

                $this->userRepository->save($user);

                Notification::addMessage(Notification::SUCCESS, 'Inscription réussie ! Vous pouvez maintenant vous connecter.');
                header('Location: /BlogPHP/userLogin');
                exit;
            } else {
                foreach ($errors as $error) {
                    Notification::addMessage(Notification::ERROR, $error);
                }
            }
        }

        echo $this->twig->render('User/userRegister.html.twig');
    }

    public function logout()
    {
        // Détruire la session et rediriger vers la page d'accueil
        session_start();
        session_destroy();
        header('Location: /BlogPHP/');
        exit;
    }

    public function editProfile()
    {
        if (!isset($_SESSION['id_user'])) {
            header('Location: /BlogPHP/userLogin');
            exit;
        }

        $userId = $_SESSION['id_user'];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $name = $_POST['name'];
            $surname = $_POST['surname'];
            $message = $_POST['message'];
            $photo = $_FILES['photo'] ?? null;
            $cvFile = $_FILES['cv_file'] ?? null;

            $user = $this->userRepository->find($userId);

            $errors = [];

            if (!Validator::validateNameAndSurname($name)) {
                $errors['name'] = 'Le prénom ne peut contenir que des lettres, des espaces, des apostrophes et des tirets';
            }

            if (!Validator::validateNameAndSurname($surname)) {
                $errors['surname'] = 'Le nom ne peut contenir que des lettres, des espaces, des apostrophes et des tirets';
            }

            $photoPath = $user->getPhoto();
            if ($photo && $photo['error'] === UPLOAD_ERR_OK) {
                $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
                if (!in_array($photo['type'], $allowedTypes)) {
                    $errors['photo'] = "Le format de l'image n'est pas valide.";
                } else {
                    $targetDir = "uploads/photos/";
                    $photoPath = $targetDir . basename($photo['name']);
                    if (!move_uploaded_file($photo['tmp_name'], $photoPath)) {
                        $errors['photo'] = "L'image n'a pas pu être téléchargée.";
                    }
                }
            }

            $cvPath = $user->getCv();
            if ($cvFile && $cvFile['error'] === UPLOAD_ERR_OK) {
                $allowedTypes = ['application/pdf'];
                if (!in_array($cvFile['type'], $allowedTypes)) {
                    $errors['cv'] = "Le format du fichier CV n'est pas valide. Seul le format PDF est accepté.";
                } else {
                    $cvTargetDir = "uploads/cv_files/";
                    $cvPath = $cvTargetDir . basename($cvFile['name']);
                    if (!move_uploaded_file($cvFile['tmp_name'], $cvPath)) {
                        $errors['cv'] = "Le fichier CV n'a pas pu être téléchargé.";
                    }
                }
            }

            if (empty($errors)) {
                $user->setName($name);
                $user->setSurname(strtoupper($surname));
                $user->setMessage($message);
                $user->setPhoto($photoPath);
                $user->setCv($cvPath);

                $this->userRepository->updateProfile($user);

                Notification::addMessage(Notification::SUCCESS, 'Profil mis à jour avec succès.');
                header('Location: /BlogPHP/home');
                exit;
            } else {
                foreach ($errors as $error) {
                    Notification::addMessage(Notification::ERROR, $error);
                }
            }
        }

        $user = $this->userRepository->find($userId);

        echo $this->twig->render('User/editProfile.html.twig', [
            'user' => $user,
            'errors' => []
        ]);
    }
}
