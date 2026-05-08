<script>

function alerta(mensaje, tipo) {
    $.notify({
        // options
        message: mensaje
    }, {
        // settings
        type: tipo
    });
}
</script>
<?php

// Initialize the session
session_start();

// Check if the user is already logged in, if yes then redirect him to welcome page
if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true){
    if (isset($_COOKIE['DOMINIO'])){
         header("location: ".$_COOKIE['URI']);
     //echo "Ingresando a uri (desde arriba): ".$_COOKIE['URI']."<br>";
     }
     else
     {
         header("location: /bamboo/index.php");
        // echo "Ingresando a index (desde arriba)<br>";
}
    exit;
}
 
// Include config file
require_once "/home/gestio10/public_html/backend/config.php";
 
// Define variables and initialize with empty values
$username = $password = "";
$username_err = $password_err = "";
 
// Processing form data when form is submitted
if($_SERVER["REQUEST_METHOD"] == "POST"){
 
    // Check if username is empty
    if(empty(trim($_POST["username"]))){
        $username_err = "Favor ingresa tu usuario.";
    } else{
        $username = trim($_POST["username"]);
    }
    
    // Check if password is empty
    if(empty(trim($_POST["password"]))){
        $password_err = "Favor ingresa tu contraseña.";
    } else{
        $password = trim($_POST["password"]);
    }
    
    // Validate credentials
    if(empty($username_err) && empty($password_err)){
        // Prepare a select statement
        $sql = "SELECT id, username, password FROM usuarios_aplicacion WHERE username = ?";

        $result = db_prepare_and_execute($link, $sql, "s", [$username]);

        if($result && $result['success']){
            if($result['num_rows'] == 1){
                $row = $result['rows'][0];
                $id = $row->id;
                $db_username = $row->username;
                $hashed_password = $row->password;

                if(password_verify($password, $hashed_password)){
                    // Password is correct, so start a new session
                    session_start();

                    // Store data in session variables
                    $_SESSION["loggedin"] = true;
                    $_SESSION["id"] = $id;
                    $_SESSION["username"] = $db_username;
                    $_SESSION["auxiliar"]="";
                    db_set_charset($link, 'utf8');
                    db_query($link, "select trazabilidad('".$_SESSION["username"]."', 'Inicia sesión', null,'usuario',null, '".$_SERVER['PHP_SELF']."')");

                    // Redirect user to welcome page
                    if (isset($_COOKIE['DOMINIO'])){
                        header("location: ".$_COOKIE['URI']);
                        setcookie('URI','',time() -1,"/");
                        setcookie('DOMINIO','',time() -1,"/");
                    }
                    else
                    {
                        header("location: /bamboo/index.php");
                    }

                } else{
                    // Display an error message if password is not valid
                    $password_err = "La contraseña ingresada no es válida.";
                    echo '<script type="text/javascript">alerta("La contraseña ingresada no es válida." ,"warning");</script>';

                }
            } else{
                // Display an error message if username doesn't exist
	echo '<script type="text/javascript">alerta("El usuario y contraseña no coinciden." ,"warning");</script>';
            }
        } else{

	echo '<script type="text/javascript">alerta("Oops! Algo salió mal. Favor intentar más tarde." ,"warning");</script>';
        }
    }
    
    // Close connection
    db_close($link);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Iniciar sesión · Bamboo Seguros</title>
<link rel="icon" href="/bamboo/images/bamboo.png">

<!-- Fuentes Bamboo -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Fraunces:opsz,wght@9..144,400;9..144,500;9..144,600&family=Varela+Round&display=swap" rel="stylesheet">

<!-- Bootstrap 4.4 + tokens Bamboo -->
<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css">
<link rel="stylesheet" href="/assets/css/bamboo/tokens.css">
<link rel="stylesheet" href="/assets/css/bamboo/components.css">
<script src="https://kit.fontawesome.com/7011384382.js" crossorigin="anonymous"></script>

<style>
  html, body {
    height: 100%;
    margin: 0;
    background: var(--bg-app);
    font-family: var(--font-sans);
    color: var(--fg-default);
  }
  .bb-login-shell {
    min-height: 100vh;
    display: grid;
    grid-template-columns: 1fr 1fr;
  }
  .bb-login-side {
    background: var(--bamboo-700);
    color: #fff;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    padding: var(--space-10) var(--space-8);
    position: relative;
    overflow: hidden;
  }
  .bb-login-side::before {
    content: '';
    position: absolute;
    inset: 0;
    background:
      radial-gradient(circle at 20% 80%, rgba(168,130,63,.18), transparent 55%),
      radial-gradient(circle at 90% 10%, rgba(255,255,255,.05), transparent 60%);
  }
  .bb-login-brand {
    display: flex; align-items: center; gap: var(--space-3);
    position: relative; z-index: 1;
  }
  .bb-login-brand img {
    width: 56px; height: 56px;
    background: rgba(255,255,255,.1);
    border-radius: var(--radius-md);
    padding: 6px;
  }
  .bb-login-brand .name {
    font-family: var(--font-brand);
    font-size: var(--text-2xl);
    line-height: 1;
  }
  .bb-login-brand .sub {
    font-size: var(--text-xs);
    text-transform: uppercase;
    letter-spacing: .14em;
    opacity: .75;
    margin-top: 2px;
  }
  .bb-login-pitch {
    position: relative; z-index: 1;
    max-width: 440px;
  }
  .bb-login-pitch h1 {
    font-family: var(--font-display);
    font-weight: 500;
    font-size: var(--text-4xl);
    line-height: 1.15;
    margin-bottom: var(--space-4);
    color: #fff;
  }
  .bb-login-pitch p {
    font-size: var(--text-md);
    color: rgba(255,255,255,.78);
    margin: 0;
  }
  .bb-login-pitch .chips {
    margin-top: var(--space-6);
    display: flex; flex-wrap: wrap; gap: var(--space-2);
  }
  .bb-login-pitch .chip {
    font-size: var(--text-xs);
    text-transform: uppercase;
    letter-spacing: .08em;
    padding: 4px 10px;
    border: 1px solid rgba(255,255,255,.25);
    border-radius: var(--radius-pill);
    color: rgba(255,255,255,.85);
  }
  .bb-login-side .foot {
    position: relative; z-index: 1;
    font-size: var(--text-xs);
    opacity: .6;
  }
  .bb-login-form {
    display: flex; align-items: center; justify-content: center;
    padding: var(--space-8);
  }
  .bb-login-card {
    width: 100%;
    max-width: 420px;
  }
  .bb-login-card h2 {
    font-family: var(--font-display);
    font-weight: 500;
    font-size: var(--text-3xl);
    margin: 0 0 4px;
  }
  .bb-login-card .subtitle {
    color: var(--fg-muted);
    margin-bottom: var(--space-6);
    font-size: var(--text-sm);
  }
  .bb-login-card .form-control {
    height: 44px;
    font-size: var(--text-md);
  }
  .bb-login-card .input-group-text {
    background: var(--bg-subtle);
    border-color: var(--border-default);
    color: var(--fg-muted);
    width: 44px;
    justify-content: center;
  }
  .bb-login-card .btn-bamboo {
    height: 46px;
    font-weight: 600;
    font-size: var(--text-md);
  }
  .bb-login-card .hint {
    text-align: center;
    margin-top: var(--space-4);
    font-size: var(--text-sm);
  }
  .bb-login-card .hint a {
    color: var(--bamboo-700);
  }
  @media (max-width: 900px) {
    .bb-login-shell { grid-template-columns: 1fr; }
    .bb-login-side  { min-height: 200px; padding: var(--space-6); }
    .bb-login-pitch h1 { font-size: var(--text-2xl); }
  }
</style>
</head>

<body>

<div class="bb-login-shell">

  <aside class="bb-login-side">
    <div class="bb-login-brand">
      <img src="/bamboo/images/bamboo.png" alt="Bamboo">
      <div>
        <div class="name">Bamboo</div>
        <div class="sub">Plataforma</div>
      </div>
    </div>

    <div class="bb-login-pitch">
      <h1>Gestión de cartera y siniestros para corredores de seguros.</h1>
      <p>Cartera, pólizas, endosos, tareas y siniestros — todo en un solo lugar.</p>
      <div class="chips">
        <span class="chip">Pólizas</span>
        <span class="chip">Endosos</span>
        <span class="chip">Tareas</span>
        <span class="chip">Siniestros</span>
        <span class="chip">Correos</span>
      </div>
    </div>

    <div class="foot">© Bamboo Seguros · gestionipn.cl</div>
  </aside>

  <main class="bb-login-form">
    <div class="bb-login-card">
      <h2>Inicio de sesión</h2>
      <div class="subtitle">Ingresa tus credenciales para acceder a la plataforma.</div>

      <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" autocomplete="on">
        <div class="form-group">
          <label for="username">Usuario</label>
          <div class="input-group">
            <div class="input-group-prepend">
              <span class="input-group-text"><i class="fas fa-user"></i></span>
            </div>
            <input type="text" class="form-control" name="username" id="username"
                   value="<?php echo htmlspecialchars($username); ?>" required autofocus>
          </div>
          <?php if (!empty($username_err)): ?>
            <div style="color:var(--danger-700);font-size:var(--text-xs);margin-top:4px"><?= htmlspecialchars($username_err) ?></div>
          <?php endif; ?>
        </div>

        <div class="form-group">
          <label for="password">Contraseña</label>
          <div class="input-group">
            <div class="input-group-prepend">
              <span class="input-group-text"><i class="fas fa-lock"></i></span>
            </div>
            <input type="password" class="form-control" name="password" id="password" required>
          </div>
          <?php if (!empty($password_err)): ?>
            <div style="color:var(--danger-700);font-size:var(--text-xs);margin-top:4px"><?= htmlspecialchars($password_err) ?></div>
          <?php endif; ?>
        </div>

        <button type="submit" class="btn btn-bamboo btn-block">Ingresar</button>
        <div class="hint"><a href="#">¿Olvidaste contraseña?</a></div>
      </form>
    </div>
  </main>

</div>

<script src="https://code.jquery.com/jquery-3.5.0.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/js/bootstrap.min.js"></script>
<script src="/assets/js/bootstrap-notify.min.js"></script>

</body>
</html>
