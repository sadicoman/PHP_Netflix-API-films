<?php

session_start();

require('src/log.php');

// if (!isset($_SESSION['connect'])) {
// 	// Utilisateur non connecté, redirection vers la page de connexion ou d'accueil
// 	header('location: index.php');
// 	exit();
// }

require_once 'src/themoviedb.php';

// Utiliser la clé API depuis le fichier de configuration
$apiKey = API_KEY;

// Construire l'URL de requête
$url = "https://api.themoviedb.org/3/movie/popular?api_key=" . $apiKey . "&language=fr-FR";

// Initialiser cURL
$curl = curl_init();

curl_setopt($curl, CURLOPT_URL, $url);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

// Exécuter la requête cURL
$response = curl_exec($curl);

// Exécuter la requête cURL
$response = curl_exec($curl);
if ($response === false) {
	echo "Erreur cURL : " . curl_error($curl);
	// Fermer la session cURL
	curl_close($curl);
	exit;
}

// Obtenir le code de réponse HTTP
$httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
if ($httpCode != 200) {
	echo "Erreur API, Code HTTP : " . $httpCode;
	// Fermer la session cURL
	curl_close($curl);
	exit;
}

// Fermer la session cURL
curl_close($curl);


// Convertir la réponse en JSON
$movies = json_decode($response, true);
if (json_last_error() !== JSON_ERROR_NONE) {
	echo "Erreur lors de la décodification JSON : " . json_last_error_msg();
	exit;
}

if (!empty($_POST['email']) && !empty($_POST['password'])) {

	require('src/connect.php');

	// VARIABLES
	$email 			= htmlspecialchars($_POST['email']);
	$password		= htmlspecialchars($_POST['password']);

	// ADRESSE EMAIL SYNTAXE
	if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

		header('location: index.php?error=1&message=Votre adresse email est invalide.');
		exit();
	}

	// CHIFFRAGE DU MOT DE PASSE
	$password = "aq1" . sha1($password . "123") . "25";

	// EMAIL DEJA UTILISE
	$req = $db->prepare("SELECT count(*) as numberEmail FROM user WHERE email = ?");
	$req->execute(array($email));

	while ($email_verification = $req->fetch()) {
		if ($email_verification['numberEmail'] != 1) {
			header('location: index.php?error=1&message=Impossible de vous authentifier correctement.');
			exit();
		}
	}

	// CONNEXION
	$req = $db->prepare("SELECT * FROM user WHERE email = ?");
	$req->execute(array($email));

	while ($user = $req->fetch()) {

		if ($password == $user['password']) {

			$_SESSION['connect'] = 1;
			$_SESSION['email']   = $user['email'];

			if (isset($_POST['auto'])) {
				setcookie('auth', $user['secret'], time() + 364 * 24 * 3600, '/', null, false, true);
			}

			header('location: index.php?success=1');
			exit();
		} else {

			header('location: index.php?error=1&Impossible de vous authentifier correctement.');
			exit();
		}
	}
}

?>
<!DOCTYPE html>
<html>

<head>
	<meta charset="utf-8">
	<title>Netflix</title>
	<link rel="stylesheet" type="text/css" href="design/default.css">
	<link rel="icon" type="image/pngn" href="img/favicon.png">
</head>

<body>

	<?php include('src/header.php'); ?>

	<section>
		<div id="login-body">

			<?php if (isset($_SESSION['connect'])) { ?>
				<ul class="nav">
					<li><?php
						if (isset($_GET['success'])) {
							echo '<div class="alert success">Vous êtes maintenant connecté.</div>';
						} ?></li>
					<li>
						<div><small><a href="logout.php">Déconnexion</a></small></div>
					</li>
				</ul>
				<h1>Bonjour !</h1>
				<p>Qu'allez-vous regarder aujourd'hui ?</p>
				<?php
				// Vérifier si la requête a réussi
				if (isset($movies['results'])) {
					echo '<div class="film__list">';
					foreach ($movies['results'] as $movie) {
						// Afficher le titre du film et l'image (poster)
						echo '<div class="film__container">';
						echo '<h3 class="film__title">' . htmlspecialchars($movie['title']) . '</h3>';
						echo '<img class="film__img" src="https://image.tmdb.org/t/p/w500' . $movie['poster_path'] . '" alt="' . htmlspecialchars($movie['title']) . '">';
						echo '</div>';
					}
					echo '</div>';
				} else {
					echo "Impossible de récupérer les films.";
				}
				?>



			<?php } else { ?>
				<h1>S'identifier</h1>

				<?php if (isset($_GET['error'])) {

					if (isset($_GET['message'])) {
						echo '<div class="alert error">' . htmlspecialchars($_GET['message']) . '</div>';
					}
				} ?>

				<form method="post" action="index.php">
					<input type="email" name="email" placeholder="Votre adresse email" required />
					<input type="password" name="password" placeholder="Mot de passe" required />
					<button type="submit">S'identifier</button>
					<label id="option"><input type="checkbox" name="auto" checked />Se souvenir de moi</label>
				</form>


				<p class="grey">Première visite sur Netflix ? <a href="./inscription.php">Inscrivez-vous</a>.</p>
			<?php } ?>
		</div>
	</section>

	<?php include('src/footer.php'); ?>
</body>

</html>