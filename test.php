<?php
namespace Blog\Twig\Controller\User;

use Blog\Twig\Controller\CoreController;
use Blog\Twig\Repository\UserRepository;
use Blog\Twig\Utils\Notification;
use Blog\Twig\Utils\Validator;
use Blog\Twig\Model\User;
use PDO;

class UserController extends CoreController {
    private $userRepository;

    public function __construct($twig, PDO $db) {
        parent::__construct($twig, $db);
        $this->userRepository = new UserRepository($db);
    }

    public function userRegister() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Récupération des données du formulaire
            $name = $_POST['name'];
            $surname = $_POST['surname'];
            $username = $_POST['username'];
            $email = $_POST['email'];
            $password = $_POST['password'];

            // Validation des données côté serveur
            $errors = [];

            if (!Validator::validateNameAndSurname($name)) {
                $errors['name'] = 'Le prénom ne peut contenir que des lettres, des espaces, des apostrophes et des tirets';
            }

            if (!Validator::validateNameAndSurname($surname)) {
                $errors['surname'] = 'Le nom ne peut contenir que des lettres, des espaces, des apostrophes et des tirets';
            }

            if (!Validator::validateUsername($username)) {
                $errors['username'] = 'Le nom d\'utilisateur ne peut contenir que des lettres, des chiffres et underscores (_)';
            }

            if (!Validator::validateEmail($email)) {
                $errors['email'] = 'Veuillez entrer une adresse e-mail valide';
            }

            if (!Validator::validatePassword($password)) {
                $errors['password'] = 'Le mot de passe doit contenir au moins 8 caractères avec au moins une majuscule, un chiffre et un caractère spécial';
            }

            // Vérification de l'unicité du nom d'utilisateur et de l'email via le UserRepository
            if ($this->userRepository->findByUsername($username)) {
                $errors['username'] = 'Ce nom d\'utilisateur est déjà utilisé. Veuillez en choisir un autre.';
            }

            if ($this->userRepository->findByEmail($email)) {
                $errors['email'] = 'Cette adresse e-mail est déjà utilisée. Veuillez en choisir une autre.';
            }

            if (!empty($errors)) {
                // Affichage du formulaire d'inscription avec les erreurs
                foreach ($errors as $error) {
                    Notification::addMessage(Notification::ERROR, $error);
                }

                echo $this->twig->render('User/userRegister.html.twig', [
                    'dbStatus' => $this->dbStatus,
                    'errors' => $errors
                ]);
                return;
            }

            // Hachage du mot de passe
            $passwordHash = password_hash($password, PASSWORD_BCRYPT);

            // Création de l'utilisateur et sauvegarde dans la base de données
            $user = new User(null, $name, strtoupper($surname), $username, $email, $passwordHash, 0);
            $this->userRepository->save($user);

            // Ajout du message de succès
            Notification::addMessage(Notification::SUCCESS, 'Inscription réussie ! Vous pouvez maintenant vous connecter.');

            // Redirection vers la page de connexion après l'inscription réussie
            header('Location: /BlogPHP/userLogin');
            exit;
        } else {
            // Affichage du formulaire d'inscription
            echo $this->twig->render('User/userRegister.html.twig', ['dbStatus' => $this->dbStatus]);
        }
    }

    public function logout() {
        // Détruire la session et rediriger vers la page d'accueil
        session_start();
        session_destroy();
        header('Location: /BlogPHP/');
        exit;
    }
}
