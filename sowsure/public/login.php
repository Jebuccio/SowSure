<?php
declare(strict_types=1);
require dirname(__DIR__) . '/includes/bootstrap.php';

if (is_authenticated()) {
    redirect('/dashboard.php');
}

$errors = [];
$identifier = '';

if (method_is('POST')) {
    verify_csrf();
    $identifier = strtolower(clean_text($_POST['identifier'] ?? '', 254));
    $password = (string) ($_POST['password'] ?? '');

    if ($identifier === '' || $password === '') {
        $errors['login'] = 'Enter your username or email and password.';
    } elseif (login_is_rate_limited()) {
        $errors['login'] = 'Too many sign-in attempts. Please wait 15 minutes and try again.';
    } else {
        try {
            $email = filter_var($identifier, FILTER_VALIDATE_EMAIL)
                ? $identifier
                : (valid_username($identifier) ? $supabase->usernameEmail($identifier) : null);

            if ($email === null) {
                throw new RuntimeException('Unknown identifier.');
            }

            $response = $supabase->authSignIn($email, $password);
            store_auth_session($response);
            clear_login_attempts();
            flash('success', 'Welcome back. Your farm records are ready.');
            redirect('/dashboard.php');
        } catch (Throwable $exception) {
            error_log('Login failed: ' . $exception->getMessage());
            record_failed_login();
            $errors['login'] = 'We could not sign you in. Check your details and try again.';
        }
    }
}

$pageTitle = 'Sign in';
$bodyClass = 'auth-page';
require dirname(__DIR__) . '/includes/header.php';
?>
<section class="auth-layout">
    <div class="auth-intro">
        <span class="eyebrow">Smart piggery management</span>
        <h1>Welcome back.</h1>
        <p>See what needs attention across your herd today.</p>
        <div class="auth-quote">
            <span class="brand-mark">S</span>
            <p>“A little clarity for every farm day.”</p>
        </div>
    </div>
    <div class="auth-card">
        <div class="auth-card-heading">
            <span class="eyebrow">Sign in securely</span>
            <h2>Open your workspace</h2>
            <p>Use your email address or username.</p>
        </div>
        <?php if (isset($errors['login'])): ?>
            <div class="form-alert" role="alert"><strong>We couldn’t sign you in</strong><span><?= e($errors['login']) ?></span></div>
        <?php endif; ?>
        <form method="post" class="stack-form" novalidate>
            <?= csrf_field() ?>
            <label>Email or username
                <input type="text" name="identifier" value="<?= e($identifier) ?>" autocomplete="username" required autofocus>
            </label>
            <label>Password
                <span class="password-field">
                    <input type="password" name="password" autocomplete="current-password" required>
                    <button type="button" data-toggle-password>Show</button>
                </span>
            </label>
            <button class="button button-wide" type="submit">Sign in</button>
        </form>
        <p class="auth-switch">New to SowSure? <a href="<?= e(url('/register.php')) ?>">Create an account</a></p>
    </div>
</section>
<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
