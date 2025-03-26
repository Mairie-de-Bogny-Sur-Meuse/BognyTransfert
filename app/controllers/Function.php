<?php
function SecureSql($input){
    
    // Supprime les espaces en début et fin de chaîne
    $input = trim($input);

    // Supprime les balises HTML et PHP
    $input = strip_tags($input);

    // Convertit les caractères spéciaux en entités HTML
    $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');

    // Ajoute des antislashs (utile pour certaines bases si on n'utilise pas PDO ou mysqli::real_escape_string)
    $input = addslashes($input);

    return $input;

}
?>