<?php

namespace Blog\Twig\Model;


class User
{
    private $id;
    private $name;
    private $surname;
    private $username;
    private $email;
    private $password;
    private $isAdmin;
    private $photo;
    private $message;
    private $cv;

    public function __construct($id, $name, $surname, $username, $email, $password, $isAdmin, $photo = null, $message = null, $cv = null)
    {
        $this->id = $id;
        $this->name = $name;
        $this->surname = $surname;
        $this->username = $username;
        $this->email = $email;
        $this->password = $password;
        $this->isAdmin = $isAdmin;
        $this->photo = $photo;
        $this->message = $message;
        $this->cv = $cv;
    }

    //getters
    public function getId()
    {
        return $this->id;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getSurname()
    {
        return $this->surname;
    }

    public function getUsername()
    {
        return $this->username;
    }

    public function getEmail()
    {
        return $this->email;
    }

    public function getPassword()
    {
        return $this->password;
    }

    public function getIsAdmin()
    {
        return $this->isAdmin;
    }

    public function getPhoto()
    {
        return $this->photo;
    }

    public function getMessage()
    {
        return $this->message;
    }

    public function getCv()
    {
        return $this->cv;
    }

    //setters
    public function setId($id)
    {
        $this->id = $id;
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    public function setSurname($surname)
    {
        $this->surname = $surname;
    }

    public function setUsername($username)
    {
        $this->username = $username;
    }

    public function setEmail($email)
    {
        $this->email = $email;
    }

    public function setPassword($password)
    {
        $this->password = $password;
    }

    public function setIsAdmin($isAdmin)
    {
        $this->isAdmin = $isAdmin;
    }
    
    public function setPhoto($photo)
    {
        $this->photo = $photo;
    }
    
    public function setMessage($message)
    {
        $this->message = $message;
    }
    
    public function setCv($cv)
    {
        $this->cv = $cv;
    }

}


