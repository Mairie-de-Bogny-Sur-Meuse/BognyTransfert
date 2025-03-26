<?php

class UserModel
{
    // Ce modèle est en veille : il n'est pas actif car aucune table 'utilisateurs' n'existe dans la base actuelle.
    // Tu peux l'activer plus tard si un système de comptes utilisateurs est mis en place.

    // Méthodes en attente d'une future implémentation :

    public function findByEmail(string $email): array|false
    {
        return false;
    }

    public function create(array $data): bool
    {
        return false;
    }

    public function updatePassword(int $id, string $newPassword): bool
    {
        return false;
    }

    public function getUploads(int $userId): array
    {
        return [];
    }
}
