<?php
declare(strict_types=1);
require dirname(__DIR__) . '/includes/bootstrap.php';

if (is_authenticated()) {
    redirect('/dashboard.php');
}

$errors = [];
$values = ['full_name' => '', 'farm_name' => '', 'email' => '', 'username' => ''];

if (method_is('POST')) {
    verify_csrf();
    $values['full_name'] = clean_text($_POST['full_name'] ?? '', 120);
    $values['farm_name'] = clean_text($_POST['farm_name'] ?? '', 120);
    $values['email'] = strtolower(clean_text($_POST['email'] ?? '', 254));
    $values['username'] = strtolower(clean_text($_POST['username'] ?? '', 30));
    $password = (string) ($_POST['password'] ?? '');
    $confirmation = (string) ($_POST['password_confirmation'] ?? '');

    validate_required($errors, 'full_name', $values['full_name'], 'Full name');
    validate_required($errors, 'farm_name', $values['farm_name'], 'Farm name');
    if (!filter_var($values['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Enter a valid email address.';
    }
    if (!valid_username($values['username'])) {
        $errors['username'] = 'Use 3–30 lowercase letters, numbers, dots, hyphens, or underscores.';
    }
    if (!valid_password($password)) {
        $errors['password'] = 'Use 8 or more characters with an uppercase letter, a number, and a special character.';
    }
    if ($password !== $confirmation) {
        $errors['password_confirmation'] = 'The passwords do not match.';
    }
    if (!isset($_POST['terms'])) {
        $errors['terms'] = 'You must agree before creating an account.';
    }

    if (!$errors) {
        try {
            if ($supabase->usernameEmail($values['username']) !== null) {
                $errors['username'] = 'That username is already in use.';
            } else {
                $response = $supabase->authSignUp($values['email'], $password, [
                    'full_name' => $values['full_name'],
                    'farm_name' => $values['farm_name'],
                    'username' => $values['username'],
                ]);

                if (!empty($response['access_token'])) {
                    store_auth_session($response);
                    flash('success', 'Your SowSure account is ready.');
                    redirect('/dashboard.php');
                }

                flash('success', 'Account created. Check your email and confirm your address before signing in.');
                redirect('/login.php');
            }
        } catch (Throwable $exception) {
            error_log('Registration failed: ' . $exception->getMessage());
            $errors['form'] = friendly_data_error($exception, 'We could not create the account. The email or username may already be in use.');
        }
    }
}

$pageTitle = 'Create account';
$bodyClass = 'auth-page';
require dirname(__DIR__) . '/includes/header.php';
?>
<section class="auth-layout">
    <div class="auth-intro">
        <span class="eyebrow">Start with the essentials</span>
        <h1>A fresh start for your farm.</h1>
        <p>Create one workspace for your sow, breeding, and care records.</p>
        <ul class="check-list">
            <li>Private records protected per account</li>
            <li>Automatic 114-day farrowing dates</li>
            <li>Works on phone, tablet, and desktop</li>
        </ul>
    </div>
    <div class="auth-card auth-card-wide">
        <div class="auth-card-heading">
            <span class="eyebrow">Create account</span>
            <h2>Name your farm workspace</h2>
            <p>All fields marked required must be completed.</p>
        </div>
        <?php if (isset($errors['form'])): ?><div class="form-alert" role="alert"><?= e($errors['form']) ?></div><?php endif; ?>
        <form method="post" class="stack-form" novalidate>
            <?= csrf_field() ?>
            <div class="form-grid">
                <label>Full name
                    <input type="text" name="full_name" value="<?= e($values['full_name']) ?>" maxlength="120" autocomplete="name" required>
                    <?php if (isset($errors['full_name'])): ?><small class="field-error"><?= e($errors['full_name']) ?></small><?php endif; ?>
                </label>
                <label>Farm name
                    <input type="text" name="farm_name" value="<?= e($values['farm_name']) ?>" maxlength="120" autocomplete="organization" required>
                    <?php if (isset($errors['farm_name'])): ?><small class="field-error"><?= e($errors['farm_name']) ?></small><?php endif; ?>
                </label>
            </div>
            <label>Email address
                <input type="email" name="email" value="<?= e($values['email']) ?>" maxlength="254" autocomplete="email" required>
                <?php if (isset($errors['email'])): ?><small class="field-error"><?= e($errors['email']) ?></small><?php endif; ?>
            </label>
            <label>Username
                <input type="text" name="username" value="<?= e($values['username']) ?>" maxlength="30" autocomplete="username" required>
                <small>3–30 lowercase letters, numbers, dots, hyphens, or underscores.</small>
                <?php if (isset($errors['username'])): ?><small class="field-error"><?= e($errors['username']) ?></small><?php endif; ?>
            </label>
            <div class="form-grid">
                <label>Password
                    <input type="password" name="password" autocomplete="new-password" required>
                    <?php if (isset($errors['password'])): ?><small class="field-error"><?= e($errors['password']) ?></small><?php endif; ?>
                </label>
                <label>Confirm password
                    <input type="password" name="password_confirmation" autocomplete="new-password" required>
                    <?php if (isset($errors['password_confirmation'])): ?><small class="field-error"><?= e($errors['password_confirmation']) ?></small><?php endif; ?>
                </label>
            </div>
            <p class="password-hint">Use 8+ characters with uppercase, number, and special character.</p>
            <label class="checkbox-label"><input type="checkbox" name="terms" value="1" required> <span>I agree to use SowSure for responsible farm record-keeping.</span></label>
            <?php if (isset($errors['terms'])): ?><small class="field-error"><?= e($errors['terms']) ?></small><?php endif; ?>
            <button class="button button-wide" type="submit">Create account</button>
        </form>
        <p class="auth-switch">Already have an account? <a href="<?= e(url('/login.php')) ?>">Sign in</a></p>
    </div>
</section>
<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
