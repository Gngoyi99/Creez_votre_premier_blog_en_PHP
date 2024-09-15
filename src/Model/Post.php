<?php

namespace Blog\Twig\Model;

class Post
{
    private $postId;
    private $title;
    private $chapo;
    private $content;
    private $userId;
    private $createdAt;
    private $updatedAt;

    public function __construct($postId, $title, $chapo, $content, $userId, $createdAt, $updatedAt = null)
    {
        $this->postId = $postId;
        $this->title = $title;
        $this->chapo = $chapo;
        $this->content = $content;
        $this->userId = $userId;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
    }

    //Getters
    public function getPostId() 
    {     
        return $this->postId; 
    }

    public function getTitle() 
    { 
        return $this->title; 
    }

    public function getChapo() 
    { 
        return $this->chapo; 
    }

    public function getContent() 
    { 
        return $this->content; 
    }

    public function getUserId() 
    { 
        return $this->userId; 
    }

    public function getCreatedAt() 
    { 
        return $this->createdAt; 
    }

    public function getUpdatedAt() 
    { 
        return $this->updatedAt; 
    }

    //Setters
    public function setTitle($title) 
    { 
        $this->title = $title; 
    }

    public function setChapo($chapo) 
    { 
        $this->chapo = $chapo; 
    }

    public function setContent($content) 
    { 
        $this->content = $content; 
    }

    public function setUpdatedAt($updatedAt) 
    { 
        $this->updatedAt = $updatedAt; 
    }
}
