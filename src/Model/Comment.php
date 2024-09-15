<?php

namespace Blog\Twig\Model;

class Comment
{
    private $commentId;
    private $content;
    private $userId;
    private $postId;
    private $createdAt;
    private $approved;

    public function __construct($commentId, $content, $userId, $postId, $createdAt, $approved)
    {
        $this->commentId = $commentId;
        $this->content = $content;
        $this->userId = $userId;
        $this->postId = $postId;
        $this->createdAt = $createdAt;
        $this->approved = $approved;
    }

    // Getters 
    public function getCommentId() 
    { 

        return $this->commentId; 
    }

    public function getContent() 
    { 
        return $this->content; 
    }

    public function getUserId() 
    { 
        return $this->userId; 
    }

    public function getPostId() 
    { 
        return $this->postId; 
    }

    public function getCreatedAt() 
    { 
        return $this->createdAt; 
    }

    public function getApproved() 
    { 
        return $this->approved; 
    }
    
    //setters
    public function setContent($content) 
    { 
        $this->content = $content; 
    }

    public function setApproved($approved) 
    { 
        $this->approved = $approved; 
    }
}
