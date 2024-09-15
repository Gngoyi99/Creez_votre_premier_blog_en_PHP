<?php
namespace Blog\Twig\Repository;

use PDO;
use Blog\Twig\Model\User;

class UserRepository
{
    private $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function find($id)
    {
        $stmt = $this->db->prepare('SELECT * FROM User WHERE id_user = :id');
        $stmt->execute(['id' => $id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            return $this->mapToUser($user);
        }

        return null;
    }

    public function findByUsername($username)
    {
        $stmt = $this->db->prepare('SELECT * FROM User WHERE username = :username');
        $stmt->execute(['username' => $username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            return $this->mapToUser($user);
        }

        return null;
    }

    public function findByEmail($email)
    {
        $stmt = $this->db->prepare('SELECT * FROM User WHERE email = :email');
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            return $this->mapToUser($user);
        }

        return null;
    }

    public function save(User $user)
    {
        if ($user->getId()) {
            $stmt = $this->db->prepare('UPDATE User SET name = :name, surname = :surname, username = :username, email = :email, password = :password, isAdmin = :isAdmin, message = :message, photo = :photo, cv = :cv WHERE id_user = :id');
            $stmt->execute([
                'name' => $user->getName(),
                'surname' => $user->getSurname(),
                'username' => $user->getUsername(),
                'email' => $user->getEmail(),
                'password' => $user->getPassword(),
                'isAdmin' => $user->getIsAdmin(),
                'message' => $user->getMessage(),
                'photo' => $user->getPhoto(),
                'cv' => $user->getCv(),
                'id' => $user->getId()
            ]);
        } else {
            $stmt = $this->db->prepare('INSERT INTO User (name, surname, username, email, password, isAdmin) VALUES (:name, :surname, :username, :email, :password, :isAdmin)');
            $stmt->execute([
                'name' => $user->getName(),
                'surname' => $user->getSurname(),
                'username' => $user->getUsername(),
                'email' => $user->getEmail(),
                'password' => $user->getPassword(),
                'isAdmin' => $user->getIsAdmin()
            ]);
            $user->setId($this->db->lastInsertId());
        }
    }

    public function updateProfile(User $user)
    {
        $stmt = $this->db->prepare('UPDATE User SET name = :name, surname = :surname, message = :message, photo = :photo, cv = :cv WHERE id_user = :id');
        $stmt->execute([
            'name' => $user->getName(),
            'surname' => $user->getSurname(),
            'message' => $user->getMessage(),
            'photo' => $user->getPhoto(),
            'cv' => $user->getCv(),
            'id' => $user->getId()
        ]);
    }

    // public function delete(User $user)
    // {
    //     $stmt = $this->db->prepare('DELETE FROM User WHERE id_user = :id');
    //     $stmt->execute(['id' => $user->getId()]);
    // }

    private function mapToUser($userData)
    {
        return new User(
            $userData['id_user'],
            $userData['name'],
            $userData['surname'],
            $userData['username'],
            $userData['email'],
            $userData['password'],
            $userData['isAdmin'],
            $userData['message'],
            $userData['photo'],
            $userData['cv']
        );
    }
}
