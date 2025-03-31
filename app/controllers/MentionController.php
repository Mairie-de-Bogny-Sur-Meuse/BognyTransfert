<?php
// controllers/MentionController.php

class MentionController {
    public function rgpd() {
        require_once __DIR__ . '/../views/mentions-rgpd.php';
    }
    public function cgu(){
        require_once __DIR__ . '/../views/mentions-cgu.php';

    }
}
