<?php
// footer.php

// ... existing code ...

?>
<style>
    body {
        display: flex;
        flex-direction: column;
        min-height: 100vh;
        margin: 0;
    }
    .content {
        flex: 1;
    }
    .custom-footer {
        background-color: #f8f9fa;
        text-align: center;
        padding: 20px;
        font-size: 14px;
        color: #6c757d;
    }
    .custom-footer ul {
        list-style-type: none;
        padding: 0;
    }
    .custom-footer li {
        display: inline;
        margin: 0 10px;
    }
    .custom-footer a {
        text-decoration: none;
        color: #007bff;
    }
    .custom-footer a:hover {
        text-decoration: underline;
    }
</style>
<div class="content">
    <!-- Contenu principal de la page -->
</div>
<footer class="custom-footer">
    <div class="container">
        <p>&copy; <?php echo date("Y"); ?> prets_materiaux.</p>
        <ul>
            <li><a href="politique-de-confidentialite.php">Politique de confidentialité</a></li>
            <li><a href="conditions-d-utilisation.php">Conditions d'utilisation</a></li>
           
            <li><a href="aide.php">Aide</a></li>
            <li><a href="mentions-legales.php">Mentions légales</a></li>
        </ul>
    </div>
</footer>
<?php
// ... existing code ...
?>
