<?php
define('IDC', dirname(__FILE__));
$start_time = microtime(true);
$title_panel = 'Login';
require 'includes/header.php';

// ************************************ //
// **********	USER LOGOUT  ********** //
// ************************************ //

if(isset($_GET['logout']))
{
	unset($_SESSION['login']);
	header('Location: ' . lite_url('login.php'));
	exit;
}


// ******************************************* //
// **********	USER IS LOGGED IN	********** //
// ******************************************* //

if (isset($_SESSION['login']) && $_SESSION['login'] == $hash) {
header('Location: ' . lite_url('index.php'));
exit;
}


// *********************************************** //
// **********	FORM HAS BEEN SUBMITTED	********** //
// *********************************************** //

else if (isset($_POST['submit'])) {

	if ($_POST['username'] == $username && $_POST['password'] == $password){
	
		//IF USERNAME AND PASSWORD ARE CORRECT SET THE LOG-IN SESSION
		$_SESSION["login"] = $hash;
		header("Location: $_SERVER[PHP_SELF]");
		
	} else {
		
		// DISPLAY FORM WITH ERROR
		echo '<div class="row justify-content-center"><div class="col-lg-6">
			<div class="card o-hidden border-0 shadow-lg mt-5">
				<div class="card bg-danger text-white shadow" style="background-color: #4a1e1e !important; border-color: #5a2d2d !important;">
                    <div class="card-body">
                      Username or password is invalid
                    </div>
                  </div></div></div></div>';
		display_login_form();
		
	}
}	
	
	
// *********************************************** //
// **********	SHOW THE LOG-IN FORM	********** //
// *********************************************** //

else { 
	display_login_form();
}

function display_login_form(){ 
$self = $_SERVER['REQUEST_URI'];?>

    <div class="row justify-content-center">

      <div class="col-lg-6">

        <div class="card o-hidden border-0 shadow-lg my-5">
          <div class="card-body p-0">
            <!-- Nested Row within Card Body -->
                <div class="p-5">
                  <div class="text-center">
                    <h1 class="h4 text-gray-900 mb-4">Welcome Back!</h1>
                  </div>
                  <form action="<?php echo $self; ?>" method="post" class="user">
                    <div class="form-group">
                      <input type="text" class="form-control form-control-user" name="username" id="username" aria-describedby="emailHelp" placeholder="Username">
                    </div>
                    <div class="form-group">
                      <input type="password" class="form-control form-control-user" name="password" id="password" placeholder="Password">
                    </div>
                    <div class="form-group">
                      <div class="custom-control custom-checkbox small">
                        <input type="checkbox" class="custom-control-input" id="customCheck">
                        <label class="custom-control-label" for="customCheck">Remember Me</label>
                      </div>
                    </div>
					<input type="submit" class="btn btn-primary btn-user btn-block" name="submit" value="Login">
                   </form>
                </div>
          </div>
        </div>

      </div>

<?php } 
$end_time = microtime(true);
echo '<!-- Execution time: ' . round(($end_time - $start_time), 4) . ' seconds -->';
?>